( function() {

  let ce = {};
  
  let last_sort_key = undefined;
  let sort_asc      = true;

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
      let message = (
        "PASSWORD RESET INFO\n" +
        "  URL: " + data.url + "\n" +
        "  userid: " + userid + "\n" +
        "  token: " + data.token
      );
      if(data.email) {
        message = message + "\n\nRecovery Email sent to: " + data.email;
      }
      alert(message);
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      internal_error(jqXHR); 
    } )
  }

  $(document).ready( function($) {
    ce.form    = $('#admin-participants');
    ce.ajaxuri = ce.form.find('input[name=ajaxuri]').val();
    ce.nonce   = ce.form.find('input[name=nonce]').val();

    ce.pwreset = ce.form.find('button.pwreset');
    ce.sort_th = ce.form.find('th[data-sort]');

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
  });

})();
