( function() {

  let ce = {};
  let saved_roles = {};
  let saved_summary_flags = 0;

  function handle_roles_submit(event)
  {
    event.preventDefault();
    hide_status();

    var sender = $(event.originalEvent.submitter);

    // handle a "remove role" button
    if(sender.hasClass('remove')) {
      var userid = sender.attr('userid');
      var role   = sender.attr('from');
      remove_role(role,userid);
      update_submit();
      return;
    }

    // handle revert button
    if(ce.revert.is(sender)) {
      revert_values();
      return;
    }

    else if(!ce.submit.is(sender)) { return }

    //
    // This is the actual submit (via ajax)
    //

    var cur_roles = current_roles();
    var summary_flags = current_summary_flags();
    var changes   = role_changes(cur_roles);

    var data = {...changes, summary_flags, 'ajax':'admin/update_roles', 'nonce':ce.nonce};
    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data:data,
    })
    .done( function(data,status,jqHXR) {
      if(data.success) { 
        ce.nonce = data.nonce;
        ce.hidden['nonce'].attr('value',data.nonce);
        saved_roles = cur_roles;
        saved_summary_flags = summary_flags;
        show_status('info','Changes Saved');
      } else {
        show_status('warning', data.error);
      }
      update_submit();
    })
    .fail( function(jqXHR,textStatus,errorThrown) { 
      ajax_error_hander(jqXHR,'update roles')
    });
  }

  function remove_role(role,userid)
  {
    var select = ce.add_selects[role];
    var tgt    = ce.form.find('li.user[userid="'+userid+'"][role="'+role+'"]');
    var name   = tgt.find('span').html();
    tgt.remove();
    select.append(new Option(name,userid));
  }

  function add_role(role,userid)
  {
      var select = ce.add_selects[role];
      var option = select.find('option[value="'+userid+'"]');
      var name   = option.html();

      select.parent().before(
        '<li class="user" userid="' + userid + '" role="' + role + '">' +
        '<button class="remove" userid="' + userid + '" from="' + role + '">-</button>' +
        '<span class="name">' + name + '</span></li>'
      );

      option.remove();
  }

  function handle_role_select(event)
  {
    hide_status();

    var role = $(this).attr('name');
    var userid = $(this).val();

    if(userid) {
      add_role(role,userid);
      update_submit();
    }
  }

  function role_changes(new_roles)
  {
    var to_add = [];
    var to_drop = [];

    // find new roles
    for( var role in new_roles ) {
      var saved_role = [];
      if( role in saved_roles ) {
        saved_role = saved_roles[role];
      }
      for( userid of new_roles[role] ) {
        if( !saved_role.includes(userid) ) {
          to_add.push([role,userid]);
        }
      }
    }

    // find dropped roles
    for( var role in saved_roles ) {
      var new_role = [];
      if( role in new_roles ) {
        new_role = new_roles[role];
      }
      for( userid of saved_roles[role] ) {
        if( !new_role.includes(userid) ) {
          to_drop.push([role,userid]);
        }
      }
    }

    var changes = {};
    if( to_add.length  > 0 )  { changes.add = to_add;  }
    if( to_drop.length > 0 ) { changes.drop = to_drop; }
    return changes;
  }

  function has_changes()
  {
    var changes = role_changes(current_roles());
    if( 'add' in changes ) { return true; }
    if( 'drop' in changes ) { return true; }

    if( current_summary_flags() !== saved_summary_flags ) { return true; }

    return false;
  }

  function update_submit()
  {
    var dirty = has_changes();

    if(dirty) {
      ce.submit.attr('disabled',false);
      ce.revert.attr('disabled',false).css('opacity',1);
    } else {
      ce.submit.attr('disabled',true);
      ce.revert.attr('disabled',true).css('opacity',0);
    }

  }

  function current_roles()
  {
    roles = { 
      // there should only be one primary-admin, but makes it easier
      //   to work with in an array as I don't need to do anything
      //   special with it when comparing new/saved values.
      primary: [ce.form.find('#primary-admin-select').val()],
    }

    // admin roles
    ce.form.find('li.user').not('.new').each( function() {
      var userid = $(this).attr('userid');
      var role   = $(this).attr('role');
      if(role in roles) { roles[role].push(userid); } 
      else              { roles[role] = [userid]; }
    });
    // summary access
    ce.summary_access_inputs.each( function() {
      var userid = $(this).attr('userid');
      const role='summary';
      if( $(this).prop('checked') ) {
        if(role in roles) { roles[role].push(userid); } 
        else              { roles[role] = [userid]; }
      }
    });
    return roles;
  }

  function current_summary_flags()
  {
    let rval = 0;
    ce.summary_flags.each( function() {
      if( $(this).prop('checked') ) { rval += Number($(this).val()); }
    });
    return rval;
  }

  function revert_values()
  {
    // admin roles

    ce.form.find('#primary-admin-select').val(saved_roles['primary'][0]);

    ce.form.find('li.user').not('.new').each( function() {
      var userid = $(this).attr('userid');
      var role   = $(this).attr('role');
      if(!((role in saved_roles)&&(saved_roles[role].includes(userid)))) {
        remove_role(role,userid);
      }
    });

    Object.entries(ce.add_selects).forEach( ([role,select]) => {
      select.find('option').not('[value=""]').each ( function() {
        var userid = $(this).val();
        if(saved_roles[role]?.includes(userid)) {
          add_role(role,userid);
        }
      });
      select.val("");
      ce.add_buttons[role].prop('disabled',true).css('visibility','hidden');
    });

    // summary access
    ce.summary_access_inputs.each( function() {
      var userid = $(this).attr('userid');
      $(this).prop('checked',saved_roles['summary']?.includes(userid));
    });

    ce.summary_flags.each( function() {
      const bit = Number($(this).val());
      $(this).prop('checked', (bit & saved_summary_flags));
    });

    update_submit();
  }

  $(document).ready(
    function($) {
    ce.form            = $('#admin-roles');
    ce.ajaxuri         = $('#admin-roles input[name=ajaxuri]').val();
    ce.nonce           = $('#admin-roles input[name=nonce]').val();
    ce.status          = $('#ttt-status');
    ce.submit          = $('#changes-submit');
    ce.revert          = $('#changes-revert');

    ce.hidden = {}
    ce.form.find('input[type=hidden]').each(
      function() { ce.hidden[$(this).attr('name')] = $(this) }
    );
    ce.add_buttons = {}
    ce.form.find('button.add').each(
      function() { ce.add_buttons[$(this).attr('to')] = $(this) }
    );
    ce.add_selects = {}
    ce.form.find('li.new.user select').each(
      function() { ce.add_selects[$(this).attr('name')] = $(this) }
    );
    
    ce.summary_access_inputs = ce.form.find('input.summary.access');
    ce.summary_flags         = ce.form.find('input.summary.flag');

    saved_roles              = current_roles();
    saved_summary_flags      = current_summary_flags();

    ce.form.on('submit',handle_roles_submit);

    for( const e of Object.values(ce.add_selects) ) {
      e.on('change',handle_role_select);
    }

    $('#primary-admin-select').on('change',update_submit);
    const inputs = ce.form.find('input[type=checkbox]');
    inputs.on('change',update_submit);

    has_change_cb = has_changes;

    if(admin_lock.has_lock) {
      update_submit();
    }
    else {
      $('button').not('[name=tab]').attr('disabled',true);
      $('select').attr('disabled',true);
      $('input').attr('disabled',true);
    }
  });

})();
