import { deepCopy } from './utils.js';

//
// Support functions
//

function update_character_count(e)
{
  const cur_length = $(this).val().length;
  const max_length = $(this).attr('maxlength');
  const cc = $(this).parent().children('.char-count');

  cc.children('.cur').text(cur_length);

  if(cur_length > 0.9*max_length) {
    cc.addClass('danger').removeClass('warning');
  } else if(cur_length > 0.75*max_length) {
    cc.addClass('warning').removeClass('danger');
  } else {
    cc.removeClass('warning danger');
  }
}

// section viewer

function section_viewer(ce,frame)
{
  const _box = $('#editor-frame div.grid.section.viewer');

  const _name              = _box.children('.name');
  const _name_value        = _name.find('div.text');

  const _labeled           = _box.children('.labeled');
  const _labeled_value     = _labeled.find('div.text');

  const _description       = _box.children('.description');
  const _description_value = _description.find('div.text');

  const _feedback          = _box.children('.feedback');
  const _feedback_value    = _feedback.find('div.text');

  function show(id,data)
  {
    _name_value.html(data.name || '');
    _labeled_value.html(data.labeled ? "YES" : "NO");
    _description_value.html( data.description || '' );
    _feedback_value.html( data.feedback || '' );

    _hints.removeClass('locked');
  }

  return {
    show:show,
  };
}

// section editor

function section_editor(ce,frame)
{
  const _box = $('#editor-frame div.grid.section.editor');

  const _name              = _box.children('.name');
  const _name_value        = _name.find('input');

  const _labeled           = _box.children('.labeled');
  const _labeled_value     = _labeled.find('select');

  const _description       = _box.children('.description');
  const _description_value = _description.find('textarea');

  const _feedback          = _box.children('.feedback');
  const _feedback_value    = _feedback.find('input');

  const _hints             = _box.find('div.hint');
  const _fields            = _box.find('input,textarea,select');

  let _cur_section_id   = null;
  let _cur_section_data = null;
  let _cur_undo_action  = null;

  _description_value.on('input change',update_character_count);

  _fields.on('change input', function(e) {
    const key   = $(this).data('key');
    const value = $(this).val();
    _cur_section_data[key] = value;
    _cur_undo_action.update(key,value);
  });

  function show(id,data)
  {
    // Determine if we can continue with the current undo action.
    //   Can do so only if it is the newest action on the undo stack.
    //   Otherwise, create a new undo action
    if( ! _cur_undo_action?.can_continue(id) ) {
      create_undo(id,data);
    }
    _cur_section_id = id;
    _cur_section_data = data;

    _name_value.val(data.name || '');
    _labeled_value.val(data.labeled ? 1 : 0);
    _description_value.val(data.description || '');
    _feedback_value.val(data.feedback || '')

    _hints.removeClass('locked');
  }

  function create_undo(id,data)
  {
    // This undo action accumulates changes to the section editor so that triggering
    //   undo or redo will treat the entire sequence of edits as a single atomic action.
    //  - When the undo manager triggers an undo, it moves the action to the redo stack.
    //      If additional edits occur after an undo, we want to start a new undo action
    //      rather than accumulating them on the current action (which has been undone)
    //      We do not want to modify the current action as this would break its future
    //      redo capability.
    //  - When the redo manager triggers a redo, it moves the action back to the undo stack.
    //      If additional edits occur after a redo, we want to continue the accumulation
    //      of edits. Simply reconnect our current undo action pointer to the action
    //      that was just moved back to the undo stack.  The following scenario illustrates
    //      a series of edits and undo/redo actions that comes back to the original state:
    //        [orig] > (edits) > undo > redo > (edits) > undo > [orig]
    //      An alternative design which was rejected was to start a new undo action
    //        after a redo to capture the next set of edits.  This would require multiple
    //        undos simply because the user did an undo/redo sequence in the middle of
    //        their edits:
    //          [orig] > (edits) > undo > redo > (edits) > undo > undo > [orig].
    //        While this is a plausible implementation, I opted for the first approach.
    //  - Finally, this undo action will not be added to the undo manager until the
    //      first edit actually occurs. There is no need to require that the user
    //      hit the undo button multiple times if there is nothing to undo. The
    //      first edit is identified by the lack of a cur_values property.

    _cur_undo_action = {
      section_id:id,     // to switch current selection in editor tree
      section_data:data, // reference to backing data
      orig_values: {
        name:data.name || '',
        labeled:data.labeled ?? 0,
        description:data.description || '',
        feedback:data.feedback || '',
      },
      can_continue(id) {
        return ce.undo_manager.isCurrent(this) && (id === this.section_id);
      },
      undo() {
        Object.entries(this.orig_values).forEach(([key,value]) => {
          _box.find('.'+key).val(value);
          this.section_data[key] = value;
        });
        if(this.section_id !== _cur_section_id) {
          $(document).trigger('SelectSectionRequest',[this.section_id]);
        }
        create_undo(id,data);
      },
      redo() { 
        Object.entries(this.cur_values).forEach(([key,value]) => {
          _box.find('.'+key).val(value);
          this.section_data[key] = value;
        });
        if(this.section_id !== _cur_section_id) {
          $(document).trigger('SelectSectionRequest',[this.section_id]);
        }
        _cur_undo_action = this;
      },
      update(key,value) {
        if('cur_values' in this) {
          this.cur_values[key] = value;
        } else {
          // first edit
          this.cur_values = deepCopy(this.orig_values);
          this.cur_values[key] = value;
          ce.undo_manager.add(this);
        }
      }
    };
  }

  return {
    show:show,
  };
}

// question viewer

function question_viewer(ce,frame)
{
  const _box = $('#editor-frame div.grid.question.viewer');

  const _type              = _box.children('.type');
  const _type_value        = _type.find('div.text');

  const _wording           = _box.children('.wording');
  const _wording_value     = _wording.find('div.text');

  const _qualifier         = _box.children('.qualifier');
  const _qualifier_value   = _qualifier.find('div.text');

  const _description       = _box.children('.description');
  const _description_value = _description.find('div.text');

  const _info              = _box.children('.info');
  const _info_label        = _info.filter('label');
  const _info_value        = _info.find('div.text');
  const _info_hint         = _info.find('.hint > div');
  const _info_hint_info    = _info_hint.filter('.info-block');
  const _info_hint_other   = _info_hint.filter('.other-type');

  const _options           = _box.children('.options');
  const _primary           = _options.filter('.primary');
  const _secondary         = _options.filter('.secondary');
  const _other             = _options.filter('.other');
  const _primary_value     = _primary.find('div.text');
  const _secondary_value   = _secondary.find('div.text');
  const _other_value       = _other.find('div.text');

  const _hints             = _box.find('div.hint');

  function show(id,data)
  {
    // As the list of fields to show depend on question type, we start by hiding
    //   all of the fields and then turning back on those that are needed based on
    //   question type.
    _box.children().hide();
    _type.show();
    _hints.hide();

    // The info field actually has a different interpretation based on if it's
    //   a real question or an information block.  We'll assume it's a real
    //   question for now and modify it for an information block if necessary
    _info_label.html('Popup Hint:');
    _info_hint.hide();

    // Now we can customize what is shown based on the question type
    switch(data.type) {
      case 'INFO':
        _type_value.html('Info Block');
        _info.show();
        _info_label.html('Info Text:');
        _info_value.html(data.info || '');
        _info_hint_info.show();
        break;

      case 'BOOL':
        _type_value.html('Simple Checkbox');
        _wording.show();
        _wording_value.html(data.wording || '');
        _qualifier.show();
        _qualifier_value.html(data.qualifier || '');
        _description.show();
        _description_value.html(data.description || '');
        _info.show();
        _info_value.html(data.info || '');
        _info_hint_other.show();
        break;

      case 'FREETEXT':
        _type_value.html('Free text');
        _wording.show();
        _wording_value.html(data.wording || '');
        _description.show();
        _description_value.html(data.description || '');
        _info.show();
        _info_value.html(data.info || '');
        _info_hint_other.show();
        break;

      case 'OPTIONS':
        _type_value.html( data.multiple ? 'Multiple Selections' : 'Single Select' );
        _wording.show();
        _wording_value.html(data.wording || '');
        _qualifier.show();
        _qualifier_value.html(data.qualifier || '');
        _description.show();
        _description_value.html(data.description || '');
        _info.show();
        _info_value.html(data.info || '');
        _info_hint_other.show();
        _options.show();

        _primary_value.find('ul').remove();
        _secondary_value.find('ul').remove();

        const primary   = data.options.filter(([id,secondary]) => !secondary);
        const secondary = data.options.filter(([id,secondary]) =>  secondary);

        if(primary.length) {
          const ul = $('<ul>').addClass('options').prependTo(_primary_value);
          primary.map(([id,]) => options[id])
          .forEach((opt) => $('<li>').text(opt).appendTo(ul));
        }

        if(secondary.length) {
          const ul = $('<ul>').addClass('options').prependTo(_secondary_value);
          secondary.map(([id,]) => options[id])
          .forEach((opt) => $('<li>').text(opt).appendTo(ul));
        }

        _other_value.html(data.other || '');

        break;
    }


  }

  return {
    show:show,
  };
}

// question editor

function question_editor(ce,frame)
{
  const _box = $('#editor-frame div.grid.question.editor');

  function show(id,data)
  {
  }

  return {
    show:show,
  };
}

// editors frame 

export default function editor_frame(ce)
{
  const _frame = $('#editor-frame');

  const _section_viewer  = section_viewer(ce,this);
  const _section_editor  = section_editor(ce,this);
  const _question_viewer = question_viewer(ce,this);
  const _question_editor = question_editor(ce,this);

  let _editable = false;

  _frame.find('.viewer, .editor').find('div.label span')
  .on('mouseenter', function(e) {
    const timeout_id = $(this).data('timeout');
    if(timeout_id) { clearTimeout(timeout_id); }
    const hint = $(this).parent().next().children('div.hint');
    $(this).data(
      'timeout',
      setTimeout(() => { hint.addClass('hover') }, 250),
    );
  })
  .on('mouseleave', function(e) {
    const timeout_id = $(this).data('timeout');
    if(timeout_id) {
      clearTimeout(timeout_id);
      $(this).data('timeout',0);
    }
    $(this).parent().next().children('div.hint').removeClass('hover');
  })
  .on('click', function(e) {
    $(this).parent().next().children('div.hint').toggleClass('locked');
  })
  ;

  function show_section(section_id,section)
  {
    _frame.removeClass('question').addClass('section');
    if(_editable) { _section_editor.show(section_id,section) }
    else          { _section_viewer.show(section_id,section) }
  }

  function show_question(question_id,question)
  {
    _frame.removeClass('question').addClass('question');
    if(_editable) { _question_editor.show(question_id,question) }
    else          { _question_viewer.show(question_id,question) }
  }

  function hide() 
  {
    _frame.removeClass('section question');
  }

  function reset(editable)
  {
    hide();
    _editable = editable;
    if(editable) { _frame.addClass('editable').removeClass('locked') }
    else         { _frame.addClass('locked').removeClass('editable') }
  }

  return {
    show_section:show_section,
    show_question:show_question,
    hide:hide,
    reset:reset,
  };
}

