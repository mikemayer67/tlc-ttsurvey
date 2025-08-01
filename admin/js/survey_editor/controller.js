import editor_tree     from './editor_tree.js';
import editor_menubar  from './editor_menubar.js';
import setup_resizer   from './resizer.js';
import section_viewer  from './section_viewer.js';
import section_editor  from './section_editor.js';
import question_viewer from './question_viewer.js';
import question_editor from './question_editor.js';

import { deepCopy } from '../utils.js';

function setup_hint_handler() 
{
  const triggers = $('#editor-frame').find('.viewer, .editor').find('div.label span')

  let _timeout_id = null;

  triggers.on('mouseenter', function(e) {
    if(_timeout_id) { clearTimeout(_timeout_id) }
    const hint = $(this).parent().next().children('div.hint');
    _timeout_id = setTimeout( function() { 
      hint.addClass('hover') 
    }, 250 );
  });

  triggers.on('mouseleave', function(e) {
    if(_timeout_id) {
      clearTimeout(_timeout_id);
      _timeout_id = null;
    }
    const hint = $(this).parent().next().children('div.hint');
    hint.removeClass('hover');
  });

  triggers.on('click', function(e) {
    const hint = $(this).parent().next().children('div.hint');
    hint.toggleClass('locked');
  });
}


export default function survey_editor(ce)
{
  const _box   = $('#content-editor');
  const _frame = $('#editor-frame');

  // Start the returned survey_editor object.
  //    We'll add more properties/methods below
  const self = {
    editable:false,
    enable()  { this.editable = true; },
    disable() { this.editable = false; },
    show()    { _box.show(); },
    hide()    { _box.hide(); },
  };
  
  const _tree     = editor_tree(ce,self);
  const _menubar  = editor_menubar(ce,self);
  const _sv       = section_viewer(ce,self);
  const _qv       = question_viewer(ce,self);
  const _se       = section_editor(ce,self);
  const _qe       = question_editor(ce,self);

  let _content = null;
  let _next_section_id  = 1;  // assigned to next new section
  let _next_question_id = 1;  // assigned to next new question
  
  setup_resizer(ce, _box.find('div.body'), $('#survey-tree'), $('#editor-frame'));
  setup_hint_handler();

  // editor content

  self.update = function(content) 
  {
    _tree.reset();
    _frame.removeClass('section question');
    _frame.toggleClass('editable',self.editable).toggleClass('locked',!self.editable);

    if(content) {
      // if editing is enabled, we want to work on a copy of the content.
      // if editing is disabled, it's ok to view the content directly.
      if(self.editable) { _content = deepCopy(content) }
      else              { _content = content; }

      _tree.update(_content);

      if(self.editable) {
        const sections = $('#survey-tree li.section').map(function() { 
          return Number($(this).data('section')); 
        }).get();
        _next_section_id = 1 + (sections.length ? Math.max(...sections) : 0);
        _next_question_id = _content.next_ids.question;
        _tree.enable(); 
      }

    } else {
      // no content... 
      _content = null;
    }

    _menubar.update_selection();
    _menubar.show(self.editable);

    ce.undo_manager?.empty();
  };

  self.can_submit = function() {
    return _tree.can_submit();
  };

  self.content = function()
  {
    const structure = _tree.survey_structure();
    const rval = {
      options: _content?.options ?? {},
      sections: {},
      questions: {},
      next_ids: _content?.next_ids ?? {},
    };

    let new_sid = 0;
    structure.forEach( (s) => {
      new_sid += 1;
      const old_sid      = s.section_id;
      const question_ids = s.question_ids;
      rval.sections[new_sid] = _content.sections[old_sid];

      let seq = 0;
      question_ids.forEach( (qid) => {
        seq += 1;
        const new_q = deepCopy( _content.questions[qid] );
        new_q.section = new_sid;
        new_q.sequence = seq;
        rval.questions[qid] = new_q;
      });
    });

    return rval;
  }

  self.cur_section_data = function(section_id, key)
  {
    return _content.sections[section_id]?.[key];
  }

  self.cur_question_data = function(question_id, key)
  {
    return _content.questions[question_id]?.[key];
  }

  self.update_section_data = function(section_id, key, value)
  {
    _content.sections[section_id][key] = value;
    _tree.update_section(section_id,key,value);
  }

  self.update_question_data = function(question_id, key, value)
  {
    _content.questions[question_id][key] = value;
    _tree.update_question(question_id,key,value);
  }

  self.update_section_error = function(section_id, key, value, error)
  {
    _tree.set_error('section',section_id,key,error);
  }

  self.update_question_error = function(question_id, key, value, error)
  {
    _tree.set_error('question',question_id,key,error);
  }

  self.update_question_type = function(question_id,type,old_type) {
    const question = _content.questions[question_id];
    question['type'] = type;
    if(type === 'SELECT_MULTI' || type == 'SELECT_ONE') {
      question.options = [];
    }
    else {
      delete question.options
    }
    _tree.update_question_type(question_id,type,old_type);
  }

  // insertion handlers
  
  self.add_new_section = function(where)
  {
    const new_section_id = _next_section_id++;
    const new_section = { name:"", description:"", labeled:true, feedback:false };
    const cur_highlight = _tree.cache_selection();

    _content.sections[ new_section_id ] = new_section;

    ce.undo_manager.add_and_exec( {
      action:'add-new-section',
      redo() {
        console.log('redo add new section');
        _tree.add_section( new_section_id, new_section, where );
      },
      undo() {
        console.log('undo add new section');
        _tree.remove_section(new_section_id);
        _tree.restore_selection(cur_highlight);
      },
    });
  };

  self.add_new_question = function(where)
  {
    const new_question_id = _next_question_id++;
    const new_question = { id:new_question_id, type:null };
    const cur_highlight = _tree.cache_selection();

    _content.questions[ new_question_id ] = new_question;

    ce.undo_manager.add_and_exec( {
      action:'add-new-question',
      redo() { 
        _tree.add_question(new_question_id, new_question, where);
      },
      undo() {
        _tree.remove_question(new_question_id);
        _tree.restore_selection(cur_highlight);
      },
    });
  };

  self.clone_question = function(data)
  {
    if( data.parent_id in _content.questions ) {
      const new_question_id = _next_question_id++;
      const new_question = deepCopy( _content.questions[data.parent_id] );
      new_question.wording = null;
      const cur_highlight = _tree.cache_selection();

      _content.questions[new_question_id] = new_question; 

      const where = { offset:1, question_id:data.parent_id };

      ce.undo_manager.add_and_exec( {
        action:'clone-question',
        redo() {
          _tree.add_question(new_question_id, new_question, where);
        },
        undo() {
          // note that we are leaving the new question in _content.questions
          //   this will make it more efficient to redo the clone later...
          //   If the form is submitted without readding it to the DOM, it
          //   simply will not be part of what gets submitted.
          _tree.remove_question(new_question_id);
          _tree.restore_selection(cur_highlight);
        },
      });
    }
  };

  // deletion handlers

  self.delete_section = function(to_delete) 
  {
    if( to_delete.length !== 1 ) { return; }
    const section_id = to_delete.data('section');
    const section = _content.sections[section_id];

    const questions = to_delete.find('li.question');
    const question_ids = questions.map( function() { return $(this).data('question') } ).get();

    const cur_highlight = _tree.cache_selection();
    const was_closed = to_delete.hasClass('closed');

    const prev = to_delete.prev();
    const where = {};
    if(prev.length === 1 ) { 
      where.offset = 1;
      where.section_id = prev.data('section');
    }

    ce.undo_manager.add_and_exec({
      action:'delete-section',
      redo() {
        _tree.remove_section(section_id);
      },
      undo() {
        const [tgt_li,tgt_ul] = _tree.add_section(section_id,section,where);
        tgt_li.toggleClass('closed',was_closed);
        question_ids.forEach( (question_id) => {
          _tree.add_question(
            question_id,
            _content.questions[question_id],
            { section_id:section_id, at_end:true },
          );
        });
        _tree.restore_selection(cur_highlight);
      },
    });

  }

  self.delete_question = function(to_delete) 
  {
    if( to_delete.length !== 1 ) { return; }

    const question_id = to_delete.data('question');
    const question = _content.questions[question_id];

    const cur_highlight = _tree.cache_selection();

    const prev = to_delete.prev();
    const where = {};
    if( prev.length === 1 ) {
      where.offset=1;
      where.question_id = prev.data('question');
    } else {
      where.section_id = to_delete.parent().parent().data('section');
    }

    ce.undo_manager.add_and_exec({
      action: 'delete-question',
      redo() { 
        _tree.remove_question(question_id);
      },
      undo() {
        _tree.add_question(question_id, question, where);
        _tree.restore_selection(cur_highlight);
      },
    });
  }

  // selection handlers

  self.select_section = function(section_id) 
  {
    if(_frame.hasClass('section') && _frame.data('id') === section_id ) { return; }

    _frame.removeClass('question').addClass('section').data('id',section_id);

    const section = _content.sections[section_id];
    if(self.editable) { _se.show(section_id,section); }
    else              { _sv.show(section_id,section); }
    _tree.select_section(section_id);
    _menubar.update_selection();
  }

  self.select_question = function(question_id) 
  {
    if(_frame.hasClass('question') && _frame.data('id') === question_id ) { return; }

    _frame.removeClass('section').addClass('question').data('id',question_id);

    const question = _content.questions[question_id];
    if(self.editable) { _qe.show(question_id,question,_content.options); }
    else              { _qv.show(question_id,question,_content.options); }
    _tree.select_question(question_id);
    _menubar.update_selection();
  }

  self.clear_selection = function()
  {
    _frame.removeClass('section question');
    _menubar.update_selection();
  }

  // content methods

  self.unused_questions = function() 
  {
    const rval = {};
    for( const qid of _tree.bullpen() ) {
      rval[qid] = _content.questions[qid];
    }
    return rval;
  }

  self.replace_question = function(old_id, new_id) 
  {
    const old_data = _content.questions[old_id];
    const new_data = _content.questions[new_id];
    _tree.replace_question(old_id, new_id, old_data, new_data);
    return new_data;
  }

  self.all_options = function() {
    return _content.options;
  }

  self.add_option = function(new_value) {
    const new_id = _content.next_ids.option;
    _content.options[new_id] = new_value;
    _content.next_ids.option += 1;
    return new_id;
  }

  self.update_option = function(id,value) {
    _content.options[id] = value;
  }

  // pass-trhough handlers

  self.move_section  = _tree.move_section;
  self.move_question = _tree.move_question;

  // return editor object

  return self;
};
