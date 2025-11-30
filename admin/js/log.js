( function() {

  let ce = {};
  let refresh_timer = null;

  function handle_submit(event) {
    event.preventDefault();
    refresh_display();
  }

  function refresh_display() {
    pause_refresh();

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: {
        'ajax':'admin/get_log',
        'nonce':ce.nonce,
        'level':ce.log_level.val(),
        'lines':ce.num_lines.val(),
      }
    })
    .done( function(data,status,jqHXR) {
      var data_str = data.join("\n");
      ce.log_display.val(data_str);
      ce.log_display.scrollTop(ce.log_display[0].scrollHeight);
    })
    .fail( function(jqXHR,textStatus,errorThrown) { 
      internal_error(jqXHR); 
    });

    resume_refresh();
  }

  function update_log_settings() {
    const new_settings = {
      'log_level':ce.log_level.val(),
      'num_lines':ce.num_lines.val(),
      'refresh_rate':ce.refresh_rate.val(),
    };
    sessionStorage.setItem('admin_log_settings',JSON.stringify(new_settings));
  }

  function pause_refresh() {
    clearTimeout(refresh_timer);
    refresh_timer=null;
  }

  function resume_refresh() {
    pause_refresh();
    var rate = ce.refresh_rate.val();
    if(rate>0) {
      refresh_timer = setTimeout(refresh_display,1000*rate);
    }
  }

  function update_refresh_timer(){
    pause_refresh();
    resume_refresh();
  }

  $(document).ready(
    function($) {
    ce.form            = $('#admin-log');
    ce.ajaxuri         = $('#admin-log input[name=ajaxuri]').val();
    ce.nonce           = $('#admin-log input[name=nonce]').val();

    ce.log_controls    = ce.form.find('.log-controls select');
    ce.log_level       = $('#log-level-select');
    ce.num_lines       = $('#num-lines-select');
    ce.refresh_rate    = $('#refresh-rate-select');
    ce.log_display     = $('#log-display');
    ce.download        = $('#log-download-link');
    ce.newtab          = $('#log-newtab-link');
    ce.submit          = $('#log-refresh-button');

    ce.hidden = {}
    ce.form.find('input[type=hidden]').each(
      function() { ce.hidden[$(this).attr('name')] = $(this) }
    );

    var settings = sessionStorage.getItem('admin_log_settings');
    if(settings !== null) {
      settings = JSON.parse(settings);
      const cur_level = settings.log_level;
      const cur_lines = settings.num_lines;
      const cur_refresh = settings.refresh_rate;
      ce.log_controls.find('option:selected').removeAttr('selected');
      ce.log_level.find('option[value="'+cur_level+'"]').attr('selected','selected');
      ce.num_lines.find('option[value="'+cur_lines+'"]').attr('selected','selected');
      ce.refresh_rate.find('option[value="'+cur_refresh+'"]').attr('selected','selected');
    }

    ce.log_controls.on('change',update_log_settings);
    update_log_settings();

    ce.newtab.on('click',function() {
      var w = window.open('','ttt_admin');

      $.ajax( {
        type: 'POST',
        url: ce.ajaxuri,
        dataType: 'json',
        data: {
          'ajax':'admin/get_log',
          'nonce':ce.nonce,
          'level':3,
          'lines':0,
        }
      })
      .done( function(data,status,jqHXR) {
        var data_str = data.join("\n");
        w.document.write("<pre>"+data_str+"</pre>");
        w.close();
      });
    });

    ce.log_level.on('change',refresh_display);

    refresh_display();

    ce.form.on('submit',handle_submit);
  });

})();
