import Sortable from '../../../js/sortable.esm.js';
import { deepCopy, update_character_count } from '../utils.js';


function validate_input(key,value) 
{
  const len = String(value).trim().length;
  var invalid_char_regex = null;

  switch(key) {
    case 'wording':
      if(len==0) { return 'missing';   } 
      if(len<4)  { return 'too short'; }
//      invalid_char_regex = new RegExp("[^\\w\\s.,;:&-?#]");
      break;
  }

  if(invalid_char_regex) {
    const invalid_char = value.match(invalid_char_regex);
    if(invalid_char) { return `invalid char (${invalid_char})`; }
  }

  return '';
}




export default function question_editor(ce,controller)
{
  const _box = $('#editor-frame div.grid.question.editor');

  const _archive            = _box.children('.archive');
  const _archive_select     = _archive.filter('.value').find('select');

  const _type               = _box.children('.type');
  const _type_value         = _type.filter('.value').find('div.text');
  const _type_select        = _type.filter('.value').find('select');

  const _wording            = _box.children('.wording');
  const _wording_value      = _wording.find('input');

  const _qualifier          = _box.children('.qualifier');
  const _qualifier_value    = _qualifier.find('input');

  const _description        = _box.children('.description');
  const _description_value  = _description.find('textarea');

  const _info               = _box.children('.info');
  const _info_label         = _info.filter('.label').find('span');
  const _info_value         = _info.find('textarea');
  const _info_maxlen        = _info.find('.char-count span.max');
  const _info_hint          = _info.find('.hint > div');
  const _info_hint_info     = _info_hint.filter('.info-block');
  const _info_hint_other    = _info_hint.filter('.other-type');

  const _options            = _box.children('.options');
  const _primary            = _options.filter('.primary');
  const _primary_selected   = _primary.find('.selected');
  const _primary_pool       = _primary.find('.pool');
  const _secondary          = _options.filter('.secondary');
  const _secondary_selected = _secondary.find('.selected');
  const _secondary_pool     = _secondary.find('.pool');
  const _other              = _options.filter('.other');
  const _other_value        = _other.find('input');

  const _hints              = _box.find('div.hint');

  let _cur_id   = null;
  let _cur_undo = null;

  const _primary_selected_sorter = new Sortable( _primary_selected[0], 
    {
      group: {name:'primary_selected', put:['primary_pool','secondary_selected']},
      animation: 150,
      draggable: '.chip',
      onEnd: handle_option_drag,
    },
  );
  const _primary_pool_sorter = new Sortable( _primary_pool[0], 
    {
      group: {name:'primary_pool', put:false},
      animation: 150,
      draggable: '.chip',
      sort: false,
      onEnd: handle_option_drag,
    },
  );
  const _secondary_selected_sorter = new Sortable( _secondary_selected[0], 
    {
      group: {name:'secondary_selected', put:['secondary_pool','primary_selected']},
      animation: 150,
      draggable: '.chip',
      onEnd: handle_option_drag,
    },
  );
  const _secondary_pool_sorter = new Sortable( _secondary_pool[0], 
    {
      group: {name:'secondary_pool', put:false},
      animation: 150,
      draggable: '.chip',
      sort: false,
      onEnd: handle_option_drag,
    },
  );

  _description_value.on('input change', update_character_count);
  _info_value.on('input change', update_character_count);

  _box.find('input,textarea')
    .on('input',handle_input)
    .on('blur',handle_input_change)
    .on('change',handle_input_change);

  function handle_input(e) 
  {
    const input = $(this);
    const timer_id = input.data('timer');
    clearTimeout(timer_id);
    input.data('timer', setTimeout( function() {
      input.removeData('timer');
      validate_and_handle_update(input);
    }, 500 ));
  }

  function handle_input_change(e)
  {
    const input = $(this);
    const timer_id = input.data('timer') ?? undefined;
    if(timer_id) {
      input.removeData('timer');
      validate_and_handle_update(input);
    }
  }

  function validate_and_handle_update(input)
  {
    const key   = input.data('key');
    const value = input.val();
    const error = validate_input(key,value);
    _box.children('.value.'+key).find('span.error').text(error?? '');
    controller.update_question_error(_cur_id,key,value,error);
    handle_update(key,value);
  }

  function handle_change(e)
  {
    const key      = $(this).data('key');
    const value    = $(this).val();
    handle_update(key,value);
  }

  function handle_update(key,value)
  {
    _cur_undo.update(key,value);
    controller.update_question_data(_cur_id,key,value);
    $(document).trigger('SurveyWasModified');
  }

  function show(id,data)
  {
    _cur_id = id;

    // As the list of fields to show depend on question type, we start by hiding
    //   all of the fields and then turning back on those that are needed based on
    //   question type.
    _box.children().hide();
    _type.show();
    _hints.removeClass('locked');
    _type_select.off('change');
    _archive_select.off('change');

    // The info field actually has a different interpretation based on if it's
    //   a real question or an information block.  We'll assume it's a real
    //   question for now and modify it for an information block if necessary
    _info_label.text('Popup Hint:');
    _info_hint.hide();
    const maxlen_other = _info_value.data('maxlen-other');
    _info_maxlen.text(maxlen_other);
    _info_value.attr('maxlength',maxlen_other);

    // Now we can customize what is shown based on the question type
    //   We'll handle the case where the type is not specified first
    //   We'll then handle all the other case where the type is specified

    if(!data.type) {
      show_new(id,data);
      return;
    }

    _type_value.text( typeLabels[data.type] ).show();
    _type_select.hide();

    switch(data.type) {
      case 'INFO': {
        const maxlen_info = _info_value.data('maxlen-info');
        _info.show();
        _info_label.text('Info Text:');
        _info_value.attr('maxlength',maxlen_info);
        _info_value.val(data.info || '').trigger('change');
        _info_maxlen.text(maxlen_info);
        _info_hint_info.show();
        break;
      }
      case 'BOOL': {
        _wording.show();
        _wording_value.val(data.wording || '');
        _qualifier.show();
        _qualifier_value.val(data.qualifier || '');
        _description.show();
        _description_value.val(data.description || '').trigger('change');
        _info.show();
        _info_value.val(data.info || '').trigger('change');
        _info_hint_other.show();
        break;
      }
      case 'FREETEXT': {
        _wording.show();
        _wording_value.val(data.wording || '');
        _description.show();
        _description_value.val(data.description || '').trigger('change');
        _info.show();
        _info_value.val(data.info || '').trigger('change');
        _info_hint_other.show();
        break;
      }
      case 'SELECT_ONE':
      case 'SELECT_MULTI': {
        _wording.show();
        _wording_value.val(data.wording || '');
        _description.show();
        _description_value.val(data.description || '').trigger('change');
        _qualifier.show();
        _qualifier_value.val(data.qualifier || '');
        _info.show();
        _info_value.val(data.info || '').trigger('change');
        _info_hint_other.show();
        _other_value.val(data.other||'');
        show_options(data.options);
        break;
      }
    }

    // Determine if we can continue with the current undo action.
    //   Can do so only if it is the newest action on the undo stack.
    //   Otherwise, create a new undo action.  Need to do this after
    //   populating the option selection fields.
    if( ! _cur_undo?.can_continue(id) ) { create_undo(id,data,controller); }
  }

  function show_new(id,data)
  {
    const bullpen = controller.unused_questions();
    if(Object.keys(bullpen).length) {
      _archive_select.find('option:not(:first)').remove();
      Object.entries(bullpen).forEach( ([id,data]) => {
        let wording = data.wording ?? '';
        if(wording.length > 125) {
          wording = wording.slice(0,125) + '...';
        }
        _archive_select.append(new Option( wording, id ));
      });
      _archive_select.val('').on('change',id,handle_archive);
      _archive.show();
    }

    _type_value.hide();
    _type_select.val('').on('change',[id,data],handle_type).show();
  }

  function show_options(data)
  {
    populate_options(data);
    _options.show();
    _primary_pool.hide();
    _secondary_pool.hide();
  }

  function populate_options(data)
  {
    _primary_selected.empty();
    _secondary_selected.empty();
    const all_options = controller.all_options();
    data.forEach( ([id,secondary]) => {
      const label = all_options[id];
      const chip = create_chip(id,label);
      if(secondary) { _secondary_selected.append(chip); } 
      else          { _primary_selected.append(chip); }
    });
  }

  function update_options(options)
  {
    controller.update_question_data(_cur_id,'options',options); 
    populate_options(options);
    update_option_pools();
  }

  function create_chip(id,label) {
    const chip = $("<div>").addClass('chip').data('id',id);
    const span = $("<span>").text(label);
    const close = $("<button class='option' type='button'>x</button>");
    return chip.append(span).append(close);
  }

  //
  // option pool
  //

  _options.on('mousedown', '.selected,.pool', toggle_option_pool);
  _options.on('click','.selected .chip button',handle_close_chip);
  _options.on('dblclick','.chip',handle_edit_chip);

  function toggle_option_pool(e) {
    // We want to yield to SortableJS if the mouse down occured in a chip element.
    if( $(e.target).closest('.chip').length > 0) { return; }
    if( $(e.target).closest('.add.option').length > 0) { return; }

    const is_primary = $(this).hasClass('primary');
    
    if(is_primary) { _secondary_pool.hide(); } else { _primary_pool.hide(); }

    const pool = is_primary ? _primary_pool : _secondary_pool;
    if(pool.is(':visible')) {
      pool.hide();
    } else {
      populate_option_pool(pool);
      pool.show();
    }
  }

  function populate_option_pool(pool) {
    const all_options = controller.all_options();

    let in_use = selected_options();
    in_use = in_use.map((x) => Number(x[0]));
    in_use = new Set(in_use);

    const add_button = pool.find('button.add.option');
    pool.find('.chip').remove();
    Object.entries(all_options).forEach( ([id,label]) => {
      if( ! in_use.has(Number(id)) ) {
        create_chip(id,label).insertBefore(add_button);
      }
    });
  }

  function update_option_pools() {
    if(_primary_pool.is(':visible'))   { populate_option_pool(_primary_pool);   }
    if(_secondary_pool.is(':visible')) { populate_option_pool(_secondary_pool); }
  }

  function selected_options() {
    const rval = [];
    _primary_selected.children('.chip').each( function(i) { 
      const id = $(this).data('id');
      rval.push([ id, false]);
    });
    _secondary_selected.children('.chip').each( function(i) { 
      const id = $(this).data('id');
      rval.push([ id, true]);
    });
    return rval;
  }

  function handle_close_chip(e) {
    const chip = $(this).closest('.chip');
    chip.remove(); 
    handle_option_change();
  }

  function handle_option_change() {
    const old_options = controller.cur_question_data(_cur_id,'options');
    const new_options = selected_options();
    ce.undo_manager.add({
      undo() { update_options(old_options); },
      redo() { update_options(new_options); },
    });
    
    controller.update_question_data(_cur_id,'options',new_options); 
    update_option_pools();
  }

  function handle_edit_chip(e) {
    const chip = $(this).closest('.chip');
    const span = chip.children('span');
    const old_value = span.text();
    const new_value = prompt('Edit option',old_value).trim();
    if(new_value && (old_value !== new_value)) {
      span.text(new_value);
      const id = chip.data('id');
      controller.update_option(id,new_value);
      update_option_pools();
    }
  }

  function handle_option_drag(e) {
    handle_option_change();
  }

  _options.find('button.add.option').on('click', function(e) {
    const new_option = prompt('New option').trim();
    if(new_option) {
      const new_id = controller.add_option(new_option);
      update_option_pools();
    }
  });

  //
  // New question handlers
  //

  function handle_archive(e)
  {
    const item = $(this);
    const new_id = item.val();
    const old_id = e.data;
    ce.undo_manager.add_and_exec({
      redo() {
        const data = controller.replace_question(old_id,new_id);
        show(new_id,data);
      },
      undo() {
        const data = controller.replace_question(new_id,old_id);
        show(old_id,data);
      },
    });
  }

  function handle_type(e)
  {
    const item = $(this);
    const type = item.val();
    const [id,data] = e.data;
    ce.undo_manager.add_and_exec({
      redo() {
        controller.update_question_type(id,type);
        show(id,data);
      },
      undo() {
        controller.update_question_type(id,null,type);
        show(id,data);
      },
    });
  }

  //---------------------------------------------------------------------------------------
  // Undo events
  //---------------------------------------------------------------------------------------
  // The section editor accumulats all input field changes into a a single undo/redo
  //   event.  The question editor, accumulates changes to each input field independently.
  //   All changes to a given input field form the atomic actions.  
  //
  // In addition, there are different create_undo methods for input/textarea fields and
  //   for option selection fields.
  //
  // This means we need to track which input the undo action is tracking.
  //
  // Other than that, see the section editor undo events for more info on how these
  //   events work with the undo/redo manager.
  //---------------------------------------------------------------------------------------
  
  function create_undo(id,data,controller,key,new_value)
  {
    // The key and new_value fields are optional. If not specified, we are simply queueing
    //   up an undo action that will be ready to handle any change to an input or 
    //   textarea field.  If they are specified, then we need to cache both the current
    //   and new values associated with the key

    function apply_action(question_id, key, value)
    {
      // no matter if we are doing an undo or a redo, we need to make sure we're 
      //   on the editor for the correct question
      controller.select_question(question_id);

      const input = _box.find('.question.'+key).val(value);
      const error = validate_input(key,value)
      _box.children('.value.'+key).find('span.error').text(error??'');
      controller.update_question_error(question_id,key,value,error);
      controller.update_question_data(question_id,key,value);
      $(document).trigger('SurveyWasModified');
    }

    _cur_undo = {
      question_id: id,
      key: null,

      can_continue(id) {
        return ce.undo_manager.isHead(this) && (id === this.question_id);
      },

      undo() {
        // the undo manager will be moving this action to the redo stack.
        //   We will need to create a new undo action to handle the next
        //   input/textarea change (after applying the undo)
        apply_action(this.question_id, this.key, this.old_value);
        create_undo(id,data,controller);
      },

      redo() {
        // the undo manager will be moving this action back ot the undo stack.
        //   We will need to point to that as the current action (after applying the redo)
        apply_action(this.question_id, this.key, this.new_value);
        _cur_undo = this;
      },

      update(key,value) {
        const isCurrent = ce.undo_manager.isHead(this);
        if(isCurrent && key === this.key) {
          // we can continue to accumulate the changes on this action
          if(value === this.old_value ) {
            // but if user manually undid the action, pop it off the undo stack
            ce.undo_manager.pop(this);
            this.key = null;
            this.old_value = '';
            this.new_vlaue = '';
          } else {
            this.new_value = value;
          }
        } 
        else if(this.key) {
          // the current undo action is already on the undo or redo stack, 
          //   but it is not on top of the undo stack
          // we need to start a new undo action and seed it with the latest update
          //   seeding it,
          // create_undo will put it on the undo stack as a key/value is provided
          create_undo(id,data,controller,key,value);
        } 
        else {
          // the current undo action is not on the undo stack, i.e. it does
          //   not yet represent a field change
          // add the update key/value and put the action on the undo stack
          this.key = key;
          this.old_value = data[key];
          this.new_value = value;
          ce.undo_manager.add(this);
        }
      },
    };

    if(key) {
      // seed the undo action with an input/textarea field update
      _cur_undo.key = key;
      _cur_undo.old_value = data[key] || '';
      _cur_undo.new_value  = new_value || '';
      // and add it to the undo manager
      ce.undo_manager.add(_cur_undo);
    } 
  }

  return {
    show:show,
  };

}
