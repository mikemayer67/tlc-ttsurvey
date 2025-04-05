( function() {

  let ce = {};
  let saved_roles = {};

  function handle_roles_submit(event)
  {
    event.preventDefault();
    hide_status();

    var sender = $(event.originalEvent.submitter);

    // handle a "remove role" button
    if(sender.hasClass('remove')) {
      var userid = sender.attr('userid');
      var role   = sender.attr('from');
      var select = ce.add_selects[role];

      var tgt = ce.form.find('li.user[userid="'+userid+'"][role="'+role+'"]');
      var name = tgt.find('span').html();
      
      tgt.remove();
      select.append(new Option(name,userid));
      update_submit();
      return;
    }

    // handle an "add role" button
    if(sender.hasClass('add')) {
      var role   = sender.attr('to');
      var select = ce.add_selects[role];
      var button = ce.add_buttons[role];
      var userid = select.val();
      var selected = select.find('option:selected');
      var name = selected.html();

      sender.parent().before(
        '<li class="user" userid="' + userid + '" role="' + role + '">' +
        '<button class="remove" userid="' + userid + '" from="' + role + '">-</button>' +
        '<span class="name">' + name + '</span></li>'
      );

      selected.remove();
      select.val('');
      button.prop('disabled',true);
      button.css('visibility','hidden');

      update_submit();
      return;
    }

    else if(!ce.submit.is(sender)) { return }

    //
    // This is the actual submit (via ajax)
    //

    var cur_roles = current_roles();
    var changes   = role_changes(cur_roles);

    var data = {...changes, 'ajax':'admin/update_roles', 'nonce':ce.nonce};
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
        show_status('info','Changes Saved');
      } else {
        if( 'bad_nonce' in data) {
          alert("Somthing got out of sync.  Reloading page.");
          location.reload();
        } else {
          show_status('warning',data.error);
        }
      }
      update_submit();
    })
    .fail( function(jqXHR,textStatus,errorThrown) { 
      internal_error(jqXHR); 
    });
  }

  function handle_role_select(event)
  {
    hide_status();

    var selected = $(this).val();
    var role = $(this).attr('name');
    console.log('handle_new_role_select' + role + ' ' + selected);
    var add_button = ce.add_buttons[role];
    if(selected == "") {
      add_button.prop('disabled',true);
      add_button.css('visibility','hidden');
    } else {
      add_button.prop('disabled',false);
      add_button.css('visibility','visible');
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
    return false;
  }

  function update_submit()
  {
    var dirty = has_changes();
    ce.submit.attr('disabled',!dirty);
  }

  function current_roles()
  {
    roles = { 
      // there should only be one primary-admin, but makes it easier
      //   to work with in an array as I don't need to do anything
      //   special with it when comparing new/saved values.
      primary: [ce.form.find('#primary-admin-select').val()],
    }

    ce.form.find('li.user').not('.new').each( function() {
      var userid = $(this).attr('userid');
      var role   = $(this).attr('role');
      if(role in roles) {
        roles[role].push(userid);
      } else {
        roles[role] = [userid];
      }
    });
    return roles;
  }

  $(document).ready(
    function($) {
    ce.form            = $('#admin-roles');
    ce.ajaxuri         = $('#admin-roles input[name=ajaxuri]').val();
    ce.nonce           = $('#admin-roles input[name=nonce]').val();
    ce.status          = $('#ttt-status');
    ce.submit          = $('#settings_submit');

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

    saved_roles = current_roles();

    ce.form.on('submit',handle_roles_submit);

    for( const e of Object.values(ce.add_selects) ) {
      e.on('change',handle_role_select);
    }

    $('#primary-admin-select').on('change',update_submit);

    has_change_cb = has_changes;

    update_submit();
  });

})();
