( function() {

  let ce = {};
  
  let last_sort_key = undefined;
  let sort_asc      = true;

  function send_reminders(e)
  {
    e.preventDefault();

    const selected = ce.selection.filter( function() {
      return $(this).prop('checked');
    });
    const userids = selected.map( function() { return $(this).val(); } ).get();

    if(userids.length > 0) {
      var data = {ajax:'admin/send_reminder_emails', userids, nonce:ce.nonce };
      $.ajax( {
        type: "POST",
        url: ce.ajaxuri,
        dataType: 'json',
        data: data,
      } )
      .done( function(data,status,jqXHR) {
        var message = "";
        if(data.sent.length > 0) {
          message = message + "Reminders sent to:\n";
          message = message + "------------------\n";
          message = message + data.sent.join(", ");
        }
        else {
          message = message + "No reminders sent";
        }

        if(data.not_needed.length > 0) {
          message = message + "\n\n";
          message = message + "No reminder needed for:\n";
          message = message + "-----------------------\n";
          message = message + data.not_needed.join(", ");
        }
        if(data.no_email.length > 0) {
          message = message + "\n\n";
          message = message + "No email address provided for:\n";
          message = message + "------------------------------\n";
          message = message + data.no_email.join(", ");
        }
        if(data.too_soon.length > 0) {
          message = message + "\n\n";
          message = message + "Too soon since last reminder for:\n";
          message = message + "---------------------------------\n";
          message = message + data.too_soon.join(", ");
        }
        if(data.failed.length > 0) {
          message = message + "\n\nS";
          message = message + "SMTP Faiilures (see log) for:\n";
          message = message + "-----------------------------\n";
          message = message + data.failed.join(", ") + "\n";
        }

        alert(message);
      })
      .fail( function(jqXHR,textStatus,errorThrown) {
        ajax_error_hander(jqXHR,'send reminder eamils');
      });
    }
  }

  function handle_select_action(e)
  {
    const value = ce.select.val();
    if(value) {
      ce.select.find('.placeholder').remove();
    }
    switch(value) {
      case 'all':
        ce.selection.prop('checked',true);
        break;
      case 'none':
        ce.selection.prop('checked',false);
        break;
      case 'no-response':
      case 'draft-only':
      case 'unsbumitted-updates':
        ce.selection.each( function() {
          $(this).prop('checked', $(this).data('status') === value);
        });
        break;
    }
    update_remind_button();
    ce.select.blur();
  }

  function sort_rows(sort_key)
  {
    if(sort_key === last_sort_key) {
      sort_asc = !sort_asc;
    }
    else {
      last_sort_key = sort_key;
      sort_asc = true;
    }

    const tbody = $("#participants tbody");

    const rowData = tbody.find("tr").get().map((tr, index) => {
      const cell = $(tr).find('td.'+sort_key);
      return {
        index: index,
        value: cell.attr('data-sort-value').toLowerCase(),
        row:   tr,
      };
    });

    rowData.sort((a, b) => {
      const cmp = a.value.localeCompare(b.value);
      return cmp !== 0 ? cmp : a.index - b.index;
    });

    if(!sort_asc) { rowData.reverse(); }

    rowData.forEach(item => tbody.append(item.row));
  }


  function handle_pwreset(userid) {

    var data = {'userid':userid, 'ajax':'admin/get_password_reset_token', 'nonce':ce.nonce};

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: data,
    } )
    .done( function(data,status,jqXHR) {
      if(data.success) {
        let message = (
          "PASSWORD RESET INFO\n" +
          "  URL: " + data.url + "\n" +
          "  userid: " + userid + "\n" +
          "  token: " + data.token
        );
        if (data.email) {
          message = message + "\n\nRecovery Email sent to: " + data.email;
        }
        alert(message);
      } else {
        // should never get here...
        internal_error(jqXHR); 
      }
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      ajax_error_hander(jqXHR,'get password reset token');
    } )
  }

  function handle_selection_change(e)
  {
    const userid = e.target.value;

    ce.select.find('.placeholder').remove();
    const custom = $('<option>', {
      value:'', text:'(custom)', disabled:true, selected:true, class:'placeholder'
    });
    custom.prependTo(ce.select);
    update_remind_button();
  }

  function update_remind_button()
  {
    const selected = ce.selection.filter( function() {
      return ($(this).data('status') !== 'submitted') && $(this).prop('checked');
    });

    ce.remind.prop('disabled', selected.length===0);
  }

  $(document).ready( function($) {
    ce.form    = $('#admin-participants');
    ce.ajaxuri = ce.form.find('input[name=ajaxuri]').val();
    ce.nonce   = ce.form.find('input[name=nonce]').val();

    ce.select = $('#action-select');
    ce.remind = $('#send-reminders');

    ce.pwreset = ce.form.find('button.pwreset');
    ce.sort_th = ce.form.find('th[data-sort]');

    ce.selection = $('#participants td.select input');

    ce.select.on('change', handle_select_action);
    ce.remind.on('click', send_reminders);

    ce.pwreset.on('click', function(e) {
      e.preventDefault();
      const userid = $(this).data('userid');
      handle_pwreset(userid);
    });

    ce.sort_th.on('click', function(e) {
      e.preventDefault();
      const sort_key = $(this).data('sort');
      sort_rows(sort_key);
    });

    ce.selection.prop('checked',false);
    ce.selection.on('change',handle_selection_change);

    const participants = $('#participants tr');
    participants.each( function() {
      const tr = $(this);
      const select    = tr.find('td.select input');
      const draft     = tr.find('td.draft').text().trim() !== '';
      const submitted = tr.find('td.submitted').text().trim() !== '';

      if( draft && submitted ) { select.data('status','unsbumitted-updates'); }
      else if( draft )         { select.data('status','draft-only'   ); }
      else if( submitted )     { select.data('status','submitted'    ); }
      else                     { select.data('status','no-response'  ); }
    });

    update_remind_button();
  });

})();
