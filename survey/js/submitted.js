
//
// Ready / Setup
//   Note that this ready will be run before the suvey.js ready... so it cannot reference any
//   of the ce elements that are initialized there
//

// What do we need to handle with respect to confirmation emails?
//   Refer to coments at top of show_submitted_page in survey/submitted.php for full context.
//
// This js file is only included for Cases 1a, 1c, and 1d.  There is nothinbg to do for
//   Case 1b as the confirmation email was already sent.
//
// Case 1a: queued
//   - Send the confirmation email via ajax and proceed to case 1b (sent)
//
// Case 1c: link
//   - The php has set up the link, nothing to do there
//   - We need to watch the email address in the profile.  If it is removed, we need to
//     replace the link with the case 1d email status message (and follow the 1d logic below).
//
// Case 1d: none
//   - The php has provided the no email status message.
//   - We need to watch the email address in the profile. If it is added, we need to
//     replace the no email status message with the send confirmation link

function send_confirmation_email(form)
{
  const ajaxuri = form.find('input[name=ajaxuri]').val();
  const nonce   = form.find('input[name=nonce]').val();
  const userid  = form.find('input[name=userid]').val();

  $.ajax({
    type:'POST',
    url:ajaxuri,
    dataType:'json',
    data:{ 
      ajax:  'survey/send_confirmation_email',
      nonce: nonce,
      userid: userid,
    },
  })
  .done( function(data,status,jqXHR) {
    let new_status = '';
    if(data.success) {
      ce.email_status = 'sent';
      new_status = 'A confirmation email was sent to '+data.email;
    } else {
      ce.email_status = 'failed';
      new_status = 'Failed to send a confirmation email: '+data.reason;
    }
    ce.email_div.fadeOut(200, function() {
      ce.email_div.text(new_status);
      ce.email_div.fadeIn(200);
    });
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    ajax_error_handler(jqXHR,'send confirmation email');
  });
}

function handle_user_profile_update(e,old_email,new_email)
{
  if(new_email) {
    if(ce.email_status === 'none') {
      ce.email_status = 'link';
      const new_status = $('<button>')
        .attr('type','submit')
        .attr('name','action')
        .attr('value','sendemail')
        .addClass('linkbutton')
        .text("Send summary to " + new_email);
      ce.email_div.fadeOut(200, function() { ce.email_div.html(new_status); ce.email_div.fadeIn(200); });
    }
  } else {
    if(ce.email_status === 'link') {
      ce.email_status = 'none';
      const new_status = "Cannot send confirmation email as no address was provided";
      ce.email_div.fadeOut(200, function() { ce.email_div.text(new_status); ce.email_div.fadeIn(200); });
    }
  }
}

$(document).ready( function() {
  const form      = $('#ttt-body #submitted');
  ce.email_status = form.find('input[name=email-status]').val();
  ce.email_div    = form.find('div.email');

  if(ce.email_status === 'queued') {
    Promise.resolve().then(() => send_confirmation_email(form));
  }

  $(document).on('UserProfileUpdated', handle_user_profile_update);

});
