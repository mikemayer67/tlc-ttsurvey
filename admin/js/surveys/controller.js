import tree            from './tree.js';
import menubar         from './menubar.js';
import section_viewer  from './views/section_viewer.js';
import section_editor  from './views/section_editor.js';
import question_viewer from './views/question_viewer.js';
import question_editor from './views/question_editor.js';
import ui_config       from './views/ui_config.js';

import { deepCopy }    from '../utils.js';
import setup_resizer   from '../resizer.js';

/**
 * Support function that sets up the event handlers that manage the popup hints
 *   used in the viewer and editor panes.  These all trigger off of the element
 *   labels in the viewer/editor pane.  
 * @returns {void}
 */
function setup_hint_handler() 
{
  const triggers = $('#editor-frame').find('.viewer, .editor').find('div.label span')

  let _timeout_id = null;

  triggers.on('mouseenter', function(e) {
    if(_timeout_id) { clearTimeout(_timeout_id) }
    const hint_id = $(this).closest('div.label').data('hint');
    const hint = $(`#${hint_id}`);
    _timeout_id = setTimeout( function() { 
      hint.addClass('hover') 
    }, 250 );
  });

  triggers.on('mouseleave', function(e) {
    if(_timeout_id) {
      clearTimeout(_timeout_id);
      _timeout_id = null;
    }
    const hint_id = $(this).closest('div.label').data('hint');
    const hint = $(`#${hint_id}`);
    hint.removeClass('hover');
  });

  triggers.on('click', function(e) {
    const hint_id = $(this).closest('div.label').data('hint');
    const hint = $(`#${hint_id}`);
    hint.toggleClass('locked');
  });
}

/**
 * Controller used to manage all content in the Survey view in the Admin Dashboard
 * 
 * Oversees other controllers which each manage portions of the view:
 *   - NavTreeController handles the navigation tree
 *   - MenubarController handles the survey editor menubar
 *   - TODO: flesh this out with additional sub controllers
 * 
 * @typedef {Object} SuveyViewController
 * @property { boolean } editable Current survey can be edited
 * @property { () => void } enable_edits 
 * @property { () => void } disable_edits
 * @property { () => void } show_content Shows the content editor/viewer
 * @property { () => void } hide_content Hides the content editor/viewer
 * @property { (content:object) => void } udpate_content 
 * @property { () => boolean } can_submit
 * @property { () => {options:object, sections:object, questions:object, next_ids:object} } content
 * @property { (section_id:number, key:string) => null|number|string } cur_section_data
 * @property { (group_id:number, key:string) => null|number|string } cur_group_data
 * @property { (question_id:number, key:string) => null|number|string } cur_question_data
 * @property { (section_id:nnumber, key:string, value:null|number|string) => void } update_section_data
 * @property { (group_id:nnumber, key:string, value:null|number|string) => void } update_group_data
 * @property { (question_id:nnumber, key:string, value:null|number|string) => void } update_question_data
 * @property { (content_type:"section"|"group"|"question", content_id:number, has_error:boolean) => void } toggle_content_error
 * @property { (question_id:number, type:string, old_type:string) => void } update_question_type
 * @property { (where:{section_id:number, offset:number}) => void } add_new_section
 * @property { (where:{TODO: update attributes ... see tree::add_question}) => void } add_new_question
 * @property { (data:object) => void } clone_question
 * @property { (to_delete:jQuery<HTMLLIElement>) => void } delete_section
 * @property { (to_delete:jQuery<HTMLLIElement>) => void } delete_question
 * @property { (section_id:number) => void } select_section
 * @property { (group_id:number) => void } select_group
 * @property { (question_id:number) => void } select_question
 * @property { () => void } clear_selection
 * @property { () => Map<number,object} } unused_questions
 * @property { (old_id:number, new_id:number) => void } replace_question
 * @property { () => object } all _options
 * @property { (value:string) => number } add_option
 * @property { (id:number, value:string) => void } update_option
 * @property { (sectionId:number, toIndex:number) => boolean } move_section
 */

/**
 * Initializes a SurveyViewController
 * @param {object} ce Container of shared "global" variables
 * @returns {SurveyViewCtontroller}
 */
export default function init(ce)
{
  const _box   = $('#content-editor');
  const _frame = $('#editor-frame');

  // Start the returned survey_editor object.
  //    We'll add more properties/methods below
  const self = {
    editable:false,
    enable_edits()  { this.editable = true; },
    disable_edits() { this.editable = false; },
    show_content()  { _box.show(); },
    hide_content()  { _box.hide(); },
  };
  
  const _tree     = tree(ce,self);
  const _menubar  = menubar(ce,self);
  const _sv       = section_viewer(ce,self);
  const _qv       = question_viewer(ce,self);
  const _se       = section_editor(ce,self);
  const _qe       = question_editor(ce,self);

  let _content = null;
  let _next_section_id  = 1;  // assigned to next new section
  let _next_question_id = 1;  // assigned to next new question
  
  setup_resizer(_box.find('div.body'), $('#survey-tree'), $('#editor-frame'));
  setup_hint_handler();

  // editor content

  /**
   * Updates all of the survey views with new survey content
   * @param {Object} content All the sruvey content and structure
   */
  self.update_content = function(content) 
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

  /**
   * Returns whether or not the controller is ok with submitting the survey updates.
   *   Queries the subcontrollers and returns true only if all concur with submitting 
   * @returns {boolean}
   */
  self.can_submit = function() {
    return _tree.can_submit();
  };

  /**
   * Returns the data structure encapsulating the current survey content/structure
   * in a format usable for submitting it via an AJAX call to the server.
   * @returns {{options:object, sections:object, questions:object, next_ids:object}}
   */
  self.content = function()
  {
    const structure = _tree.survey_structure();
    const rval = {
      options: _content?.options ?? {},
      sections: {},
      questions: {},
      next_ids: _content?.next_ids ?? {},
    };

    structure.forEach( (s,s_idx) => {
      const sid            = s.section_id;
      const new_s          = deepCopy( _content.sections[sid] );
      new_s.sequence       = s_idx+1;
      rval.sections[s_idx] = new_s;

      s.question_ids.forEach( (qid,q_idx) => {
        const new_q         = deepCopy( _content.questions[qid] );
        new_q.sequence      = q_idx+1;
        new_q.section       = sid;
        rval.questions[qid] = new_q;
      });
    });

    return rval;
  }

  /**
   * Returns the current value in the survey editor for the specified section property 
   * @param {number} section_id 
   * @param {string} key 
   * @returns {null|number|string}
   */
  self.cur_section_data = function(section_id, key)
  {
    return _content.sections[section_id]?.[key];
  }

  /**
   * Returns the current value in the survey editor for the specified group property 
   * @param {number} group_id 
   * @param {string} key 
   * @returns {null|number|string}
   */
  self.cur_group_data = function(group_id, key)
  {
    return _content.groups[group_id]?.[key];
  }

  /**
   * Returns the current value in the survey editor for the specified question property 
   * @param {number} question_id 
   * @param {string} key 
   * @returns {null|number|string}
   */
  self.cur_question_data = function(question_id, key)
  {
    return _content.questions[question_id]?.[key];
  }

  /**
   * Updates the value of the specified section property in the editor and navigation tree
   * @param {number} section_id 
   * @param {string} key 
   * @param {null|number|string} value 
   */
  self.update_section_data = function(section_id, key, value)
  {
    _content.sections[section_id][key] = value;
    _tree.update_section(section_id,key,value);
  }

  /**
   * Updates the value of the specified group property in the editor and navigation tree
   * @param {number} group_id 
   * @param {string} key 
   * @param {null|number|string} value 
   */
  self.update_group_data = function(group_id, key, value)
  {
    _content.groups[group_id][key] = value;
    _tree.update_group(group_id,key,value);
  }

  /**
   * Updates the value of the specified question property in the editor and navigation tree
   * @param {number} question_id 
   * @param {string} key 
   * @param {null|number|string} value 
   */
  self.update_question_data = function(question_id, key, value)
  {
    _content.questions[question_id][key] = value;
    _tree.update_question(question_id,key,value);
  }

  /**
   * Toggles the error state of theh specified element in the survey content
   * This function simply notifies the subcontrollers of the error state.
   * @param {"section"|"group"|"question"} content_type 
   * @param {number} content_id 
   * @param {boolean} has_error 
   */
  self.toggle_content_error  = function(content_type, content_id, has_error) {
    _tree.toggle_error(content_type, content_id, has_error);
  }

  /**
   * Modifies question property attributes based on new question type 
   *   and notifies the navigation tree of the change
   * @param {number} question_id 
   * @param {string} type 
   * @param {string} old_type 
   */
  self.update_question_type = function(question_id,type,old_type) {
    const question = _content.questions[question_id];
    question['type'] = type;
    if(type === 'SELECT_MULTI' || type === 'SELECT_ONE') {
      question.layout = ui_config.layout.select_default;
      question.options = [];
    }
    else if (type === 'BOOL') {
      question.layout = ui_config.layout.bool_default;
      delete question.options;
    }
    else {
      delete question.layout;
      delete question.options;
    }
    question.grouped = ui_config.grouped.default_value;
    _tree.update_question_type(question_id,type,old_type);
  }

  // insertion handlers
  
  /**
   * Adds a new section to the survey content, notifies the navigation tree
   *   of the new section, and registers the addition with the undo manager
   * @param {{section_id:number, offset:number}} where 
   * @returns {void}
   */
  self.add_new_section = function(where)
  {
    const new_section_id = _next_section_id++;
    const new_section = { 
      section_id:new_section_id,
      name:"", 
      intro:"", 
      collapsible:1, 
    };
    const cur_highlight = _tree.current_selection();

    _content.sections[ new_section_id ] = new_section;

    ce.undo_manager.add_and_exec( {
      action:'add-new-section',
      redo() {
        _tree.add_section( new_section_id, new_section.name, where );
      },
      undo() {
        _tree.remove_section(new_section_id);
        _tree.restore_selection(cur_highlight);
      },
    });
  };

  // TODO: Add add_new_group method

  /**
   * Adds a new question to the survey content, notifies the navigation tree
   *   of the new question, and registers the addition with the undo manager
   * @param {{TODO: update attributes ... see tree::add_question }} where 
   * @returns {void}
   */
  self.add_new_question = function(where)
  {
    const new_question_id = _next_question_id++;
    const new_question = { id:new_question_id, type:null };
    const cur_highlight = _tree.current_selection();

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

  /**
   * Creates a new question as a clone of an existing one, adds it to the
   *   survey content and navigation tree, and registers this action with
   *   the undo manager.
   * 
   * @param {object} data Question details
   */
  self.clone_question = function(data)
  {
    if( data.parent_id in _content.questions ) {
      const new_question_id = _next_question_id++;
      const new_question = deepCopy( _content.questions[data.parent_id] );
      new_question.wording = null;
      new_question.id = new_question_id;
      const cur_highlight = _tree.current_selection();

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

  /**
   * Removes the specified section element from the survey content and
   *   navigation tree and registers the deletion with the undo manager.
   * @param {jQuery<HTMLLIElement>} to_delete 
   * @returns {void}
   */
  self.delete_section = function(to_delete) 
  {
    if( to_delete.length !== 1 ) { return; }
    const section_id = to_delete.data('section');
    const section = _content.sections[section_id];

    const questions = to_delete.find('li.question');
    const question_ids = questions.map( function() { return $(this).data('question') } ).get();

    const cur_highlight = _tree.current_selection();
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
        const [tgt_li,tgt_ul] = _tree.add_section(section_id,section.name,where);
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

  // TODO: Add delete_group

  /**
   * Removes the specified question element from the survey content and
   *   navigation tree and registers the deletion with the undo manager.
   * @param {jQuery<HTMLLIElement>} to_delete 
   * @returns {void}
   */
  self.delete_question = function(to_delete) 
  {
    if( to_delete.length !== 1 ) { return; }

    const question_id = to_delete.data('question');
    const question = _content.questions[question_id];

    const cur_highlight = _tree.current_selection();

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

  /**
   * Sets the specified section as the active selection in the navigation
   *   tree and notifies the menubar
   * @param {number} section_id 
   * @returns {void}
   */
  self.select_section = function(section_id) 
  {
    if(_frame.hasClass('section') && _frame.data('id') === section_id ) { return; }

    _frame.removeClass('group question').addClass('section').data('id',section_id);

    const section = _content.sections[section_id];
    if(self.editable) { _se.show(section_id,section); }
    else              { _sv.show(section_id,section); }
    _tree.select_section(section_id);
    _menubar.update_selection();
  }

  /**
   * Sets the specified group as the active selection in the navigation
   *   tree and notifies the menubar
   * @param {number} group_id 
   * @returns {void}
   */
  self.select_group = function(group_id)
  {
    if(_frame.hasClass('group') && _frame.data('id') === group_id ) { return; }

    _frame.removeClass('section question').addClass('group').data('id',group_id);

    const group = _content.groups[group_id];
    alert("need to add group editor/viewer");
    _tree.select_group(group_id);
    _menubar.update_selection();
  }

  /**
   * Sets the specified question as the active selection in the navigation
   *   tree and updates the menubar
   * @param {number} question_id 
   * @returns {void}
   */
  self.select_question = function(question_id) 
  {
    if(_frame.hasClass('question') && _frame.data('id') === question_id ) { return; }

    _frame.removeClass('section group').addClass('question').data('id',question_id);

    const question = _content.questions[question_id];
    if(self.editable) { _qe.show(question_id,question,_content.options); }
    else              { _qv.show(question_id,question,_content.options); }
    _tree.select_question(question_id);
    _menubar.update_selection();
  }

  /**
   * Unset active selection
   * @returns {void}
   */
  self.clear_selection = function()
  {
    _frame.removeClass('section group question');
    _menubar.update_selection();
  }

  // content methods

  /**
   * Returns a Map of the all content questions not currently in
   *   use in the survey.
   * @returns {Map<number,object>}
   */
  self.unused_questions = function() 
  {
    const rval = new Map();
    for( const qid of _tree.bullpen() ) {
      rval.set(Number(qid), _content.questions[qid]);
    }
    return rval;
  }

  /**
   * Swaps out one question in the navigation tree for another
   * @param {number} old_id ID of the question to be replaced
   * @param {number} new_id ID of the question replacing it
   * @returns 
   */
  self.replace_question = function(old_id, new_id) 
  {
    const old_data = _content.questions[old_id];
    const new_data = _content.questions[new_id];
    _tree.replace_question(old_id, new_id, old_data, new_data);
    return new_data;
  }

  /**
   * Returns all option values defined for the current survey
   * @returns {object} 
   * 
   */
  self.all_options = function() {
    return _content.options;
  }

  /**
   * Adds a new option string to the survey content and returns its
   *   option ID
   * @param {string} new_value 
   * @returns {number} Option ID assigned to the new option 
   */
  self.add_option = function(new_value) {
    const new_id = _content.next_ids.option;
    _content.options[new_id] = new_value;
    _content.next_ids.option += 1;
    return new_id;
  }

  /**
   * Updates the option value for the specified option ID
   * @param {number} id 
   * @param {string} value 
   * @returns {void}
   */
  self.update_option = function(id,value) {
    _content.options[id] = value;
  }

  // pass-trhough handlers

  self.move_section  = _tree.move_section;
  // TODO: add support for moving questions and groups
  // self.move_question = _tree.move_question;

  // return controller object

  return self;
};
