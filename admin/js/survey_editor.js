import editor_tree from './editor_tree.js';
import editor_menubar from './editor_menubar.js';
import setup_editor_resizer from './editor_resizer.js';
import editors from './editors.js';
import { deepCopy } from './utils.js';

export default function survey_editor(ce)
{
  const _content_editor  = $('#content-editor');

  const _editor_tree    = editor_tree(ce);
  const _editor_menubar = editor_menubar(ce);
  const _editors        = editors(ce);

  let _editable = false;
  let _content = null;
  let _next_section_id = 1;
  let _next_question_id = 1;

  setup_editor_resizer(ce,_editor_tree);

  // editor content

  function update(content) {

    _editor_tree.reset();
    _editors.reset(_editable);

    if(content) {
      // if editing is enabled, we want to work on a copy of the content.
      // if editing is disabled, it's ok to view the content directly.
      if(_editable) { _content = deepCopy(content) }
      else          { _content = content; }

      _editor_tree.update(_content);

      if(_editable) {
        const sections = $('#survey-tree li.section').map(function() { 
          return Number($(this).data('section')); 
        }).get();
        _next_section_id = 1 + Math.max(...sections)
        _next_question_id = _content.next_ids.question;
        _editor_tree.enable(); 
      }

    } else {
      // no content... 
      _content = null;
    }

    _editor_menubar.show(_editable);

    ce.undo_manager?.empty();
  }

  // insertion handlers
  
  $(document).on('AddNewSection',function(e,where) {
    const new_section_id = _next_section_id++;
    const new_section = { name:"", description:"", show:false, feedback:false };
    const cur_highlight = _editor_tree.cache_selection();

    _content.sections[ new_section_id ] = new_section;

    ce.undo_manager.add_and_exec( {
      redo() {
        _editor_tree.add_section( new_section_id, new_section, where );
      },
      undo() {
        _editor_tree.remove_section(new_section_id);
        _editor_tree.restore_selection(cur_highlight);
      },
    });
  });

  $(document).on('AddNewQuestion',function(e,where) {
    const new_question_id = _next_question_id++;
    const new_question = { type:null };
    const cur_highlight = _editor_tree.cache_selection();

    _content.questions[ new_question_id ] = new_question;

    ce.undo_manager.add_and_exec( {
      redo() { 
        _editor_tree.add_question(new_question_id, new_question, where);
      },
      undo() {
        _editor_tree.remove_question(new_question_id);
        _editor_tree.restore_selection(cur_highlight);
      },
    });
  });

  $(document).on('CloneQuestion',function(e,data) {
    if( data.parent_id in _content.questions ) {
      const new_question_id = _next_question_id++;
      const new_question = deepCopy( _content.questions[data.parent_id] );
      new_question.wording = null;
      const cur_highlight = _editor_tree.cache_selection();

      _content.questions[new_question_id] = new_question; 

      const where = { offset:1, question_id:data.parent_id };

      ce.undo_manager.add_and_exec( {
        redo() {
          _editor_tree.add_question(new_question_id, new_question, where);
        },
        undo() {
          // note that we are leaving the new question in _content.questions
          //   this will make it more efficient to redo the clone later...
          //   If the form is submitted without readding it to the DOM, it
          //   simply will not be part of what gets submitted.
          _editor_tree.remove_question(new_question_id);
          _editor_tree.restore_selection(cur_highlight);
        },
      });
    }
  });

  // deletion handlers

  function delete_section(to_delete) {
    if( to_delete.length !== 1 ) { return; }
    const section_id = to_delete.data('section');
    const section = _content.sections[section_id];

    const questions = to_delete.find('li.question');
    const question_ids = questions.map( function() { return $(this).data('question') } ).get();

    const cur_highlight = _editor_tree.cache_selection();
    const was_closed = to_delete.hasClass('closed');

    const prev = to_delete.prev();
    const where = {};
    if(prev.length === 1 ) { 
      where.offset = 1;
      where.section_id = prev.data('section');
    }

    ce.undo_manager.add_and_exec({
      redo() {
        _editor_tree.remove_section(section_id);
      },
      undo() {
        const [tgt_li,tgt_ul] = _editor_tree.add_section(section_id,section,where);
        if(was_closed) { tgt_li.addClass('closed') } else { tgt_li.removeClass('closed') }
        question_ids.forEach( (question_id) => {
          _editor_tree.add_question(
            question_id,
            _content.questions[question_id],
            { section_id:section_id, at_end:true },
          );
        });
        _editor_tree.restore_selection(cur_highlight);
      },
    });

  }
  $(document).on('RequestDeleteSection',function(e,sid) { delete_section(sid); } );


  function delete_question(to_delete) {
    if( to_delete.length !== 1 ) { return; }

    const question_id = to_delete.data('question');
    const question = _content.questions[question_id];

    const cur_highlight = _editor_tree.cache_selection();

    const prev = to_delete.prev();
    const where = {};
    if( prev.length === 1 ) {
      where.offset=1;
      where.question_id = prev.data('question');
    } else {
      where.section_id = to_delete.parent().parent().data('section');
    }

    ce.undo_manager.add_and_exec({
      redo() { 
        _editor_tree.remove_question(question_id);
      },
      undo() {
        _editor_tree.add_question(question_id, question, where);
        _editor_tree.restore_selection(cur_highlight);
      },
    });
  }
  $(document).on('RequestDeleteQuestion',function(e,eid) { delete_question(eid); } );

  // selection handlers

  $(document).on('SectionSelected', function(e,section_id) { 
    const section = _content.sections[section_id];
    _editors.show_section(section_id,section)
  });

  $(document).on('QuestionSelected', function(e,question_id) { 
    const question = _content.questions[question_id];
    _editors.show_question(question_id,question)
  });

  $(document).on('SelectionCleared', function(e) { 
    _editors.hide();
  });

  // return editor object

  return {
    show() { _content_editor.show(); },
    hide() { _content_editor.hide(); },
    disable() { _editable = false },
    enable() { _editable = true },
    update: update,
  };
};
