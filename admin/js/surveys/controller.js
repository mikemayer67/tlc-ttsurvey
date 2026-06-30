import tree            from './tree.js';
import menubar         from './menubar.js';
import section_viewer  from './views/section_viewer.js';
import section_editor  from './views/section_editor.js';
import group_viewer    from './views/group_viewer.js';
import group_editor    from './views/group_editor.js';
import question_viewer from './views/question_viewer.js';
import question_editor from './views/question_editor.js';
import ui_config       from './views/ui_config.js';

import { deepCopy }    from '../utils.js';
import setup_resizer   from '../resizer.js';

/// <reference path="./data-types.js" />

/**
 * Support function that sets up the event handlers that manage the popup hints
 *   used in the viewer and editor panes.  These all trigger off of the element
 *   labels in the viewer/editor pane.  
 * @returns {void}
 */
function setup_hint_handler() 
{
  const $triggers = $('#editor-frame').find('.viewer, .editor').find('div.label span')

  let _timeout_id = null;

  $triggers.on('mouseenter', function(e) {
    if(_timeout_id) { clearTimeout(_timeout_id) }
    const hint_id = $(this).closest('div.label').data('hint');
    const $hint = $('#' + hint_id);
    _timeout_id = setTimeout( function() { 
      $hint.addClass('hover') 
    }, 250 );
  });

  $triggers.on('mouseleave', function(e) {
    if(_timeout_id) {
      clearTimeout(_timeout_id);
      _timeout_id = null;
    }
    const hint_id = $(this).closest('div.label').data('hint');
    const $hint = $('#' + hint_id);
    $hint.removeClass('hover');
  });

  $triggers.on('click', function(e) {
    const hint_id = $(this).closest('div.label').data('hint');
    const $hint = $('#' + hint_id);
    $hint.toggleClass('locked');
  });
}

/**
 * Controller used to manage all content in the Survey view in the Admin Dashboard
 * 
 * Oversees other controllers which each manage portions of the view:
 *   - NavTreeController handles the navigation tree
 *   - MenubarController handles the survey editor menubar
 * 
 * @typedef {Object} SuveyViewController
 * @property { boolean } editable Current survey can be edited
 * @property { () => void } enable_edits 
 * @property { () => void } disable_edits
 * @property { () => void } show_content Shows the content editor/viewer
 * @property { () => void } hide_content Hides the content editor/viewer
 * @property { (content:Object) => void } udpate_content 
 * @property { () => boolean } can_submit
 * @property { () => SurveyContent } content
 * @property { (section_id:number, key:string) => null|number|string } cur_section_data
 * @property { (group_id:number, key:string) => null|number|string } cur_group_data
 * @property { (question_id:number, key:string) => null|number|string } cur_question_data
 * @property { (section_id:number, key:string, value:null|number|string) => void } update_section_data
 * @property { (group_id:number, key:string, value:null|number|string) => void } update_group_data
 * @property { (question_id:number, key:string, value:null|number|string) => void } update_question_data
 * @property { (content_type:ContentType, content_id:number, has_error:boolean) => void } toggle_content_error
 * @property { (question_id:number, type:string, old_type:string) => void } update_question_type
 * @property { (where:{section_id:number, offset:number}) => void } add_new_section
 * @property { (where:WhereToAddQuestion) => void } add_new_question
 * @property { (section_id:number) => void } add_group_to_section
 * @property { (question_id:number) => void } add_group_for_question
 * @property { (group_id:number) => void } ungroup
 * @property { (data:QuestionInfo) => void } clone_question
 * @property { ($delete_li:jQuery<HTMLLIElement>) => void } delete_section
 * @property { ($delete_li:jQuery<HTMLLIElement>) => void } delete_group
 * @property { ($delete_li:jQuery<HTMLLIElement>) => void } delete_question
 * @property { (section_id:number) => void } select_section
 * @property { (group_id:number) => void } select_group
 * @property { (question_id:number) => void } select_question
 * @property { () => void } clear_selection
 * @property { () => Map<number,QuestionInfo} } unused_questions
 * @property { (old_id:number, new_id:number) => void } replace_question
 * @property { (value:string) => number } add_option
 * @property { (id:number, value:string) => void } update_option
 * @property { (section_id:number, to_index:number) => boolean } move_section
 * @property { (groupId:number, to_section_id:number, to_index:number) => boolean } move_group
 * @property { (question_id:number, to_type:"section"|"group", to_id:number, to_index:number) => void } move_question
 */

/**
 * Initializes a SurveyViewController
 * @param {RuntimeContext} ce Container of shared "global" variables
 * @returns {SurveyViewCtontroller}
 */
export default function init(ce)
{
  const _$box   = $('#content-editor');
  const _$frame = $('#editor-frame');

  // Start the returned survey_editor object.
  //    We'll add more properties/methods below
  const self = {
    editable:false,
    enable_edits()  { this.editable = true; },
    disable_edits() { this.editable = false; },
    show_content()  { _$box.show(); },
    hide_content()  { _$box.hide(); },
  };
  
  const _tree           = tree(ce,self);
  const _menubar        = menubar(ce,self);
  const _sectionViewer  = section_viewer(ce,self);
  const _groupViewer    = group_viewer(ce,self);
  const _questionViewer = question_viewer(ce,self);
  const _sectionEditor  = section_editor(ce,self);
  const _groupEditor    = group_editor(ce,self);
  const _questionEditor = question_editor(ce,self);

  let _content = null;
  let _next_section_id  = 1;  // assigned to next new section
  let _next_question_id = 1;  // assigned to next new question
  
  setup_resizer(_$box.find('div.body'), $('#survey-tree'), $('#editor-frame'));
  setup_hint_handler();

  // editor content

  /**
   * Updates all of the survey views with new survey content
   * @param {Object} content All the sruvey content and structure
   */
  self.update_content = function(content) 
  {
    _tree.reset();
    _$frame.find('div.content-header').text('');
    _$frame.removeClass('section question group');
    _$frame.toggleClass('editable',self.editable).toggleClass('locked',!self.editable);

    if(content) {
      // if editing is enabled, we want to work on a copy of the content.
      // if editing is disabled, it's ok to view the content directly.
      if(self.editable) { _content = deepCopy(content) }
      else              { _content = content; }

      _tree.update(_content);

      if(self.editable) {
        const section_ids = $('#survey-tree li.section').map(function() { 
          return Number($(this).data('item-id')); 
        }).get();
        _next_section_id = 1 + (section_ids.length ? Math.max(...section_ids) : 0);
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
   * @returns {SurveyContent}
   */
  self.content = function()
  {
    const rval = {
      options: _content?.options ?? {},
      sections: {},
      groups: {},
      questions: {},
      next_ids: _content?.next_ids ?? {},
    };

    // Create a new data object based on the section, group, and question
    //   data in _content, but a layout based on the current survey navigation
    //   tree.  The new content data object should have the same structure as
    //   the existing _content data.
    
    // Loop over the first layer of the tree structure, i.e. the section data
    const tree_structure = _tree.survey_structure();
    tree_structure.forEach( function(section,section_index) {
      // clone the section data from the existing content data
      //   and update its section ID based on the current survey tree
      // we will update the section content shortly
      const new_section = deepCopy(_content.sections[section.section_id]);
      const new_section_id =  1 + section_index;
      new_section.section_id = new_section_id;

      // populate the new section's content as well as fleshing out the
      //   groups and questions attributes of the rval
      new_section.content = section.content.map( function(item, item_index) {
        if(item.question_id) {
          const new_question = deepCopy(_content.questions[item.question_id]);
          rval.questions[item.question_id] = new_question;
          return { type:'question', id:item.question_id };
        } else if(item.group_id) {
          const new_group = deepCopy(_content.groups[item.group_id]);
          new_group.content = [...item.content]; // copy content
          rval.groups[item.group_id] = new_group;
          item.content.map( function(question_id,question_index) {
            const new_question = deepCopy(_content.questions[question_id]);
            rval.questions[question_id] = new_question;
          });
          return { type:'group', id:item.group_id};
        }
      });
      rval.sections[new_section_id] = new_section;
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
   * @param {ItemType} content_type 
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
    _tree.update_question_type(question_id,type,old_type);
  }

  // insertion handlers
  
  /**
   * Adds a new section to the survey content, notifies the navigation tree
   *   of the new section, and registers the addition with the undo manager
   * @param {WhereRelativeToSection} where 
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

  /**
   * Adds a new question to the survey content, notifies the navigation tree
   *   of the new question, and registers the addition with the undo manager
   * @param {WhereToAddQuestion} where 
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
   * @param {QuestionInfo} data Question details
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

      const where = { offset:1, ref_type:'question', ref_id:data.parent_id };

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

  // group handlers

  /**
   * Creates a new group with a new/unique group ID
   * @param {string} [name="Untitled Group"]
   * @returns {GroupInfo}
   */
  self._create_new_group = function(name='Untitled Group')
  {
    const current_group_ids = Object.keys(_content.groups).map((x) => Number(x));
    const group_id = 1 + Math.max(...current_group_ids);
    return { group_id, name, content: [] };
  }

  /**
   * Adds a new group to (end of) a section
   * @param {number} section_id
   * @returns {void}
   */
  self.add_group_to_section = function(section_id) 
  {
    const cur_highlight = _tree.current_selection();
    const new_group = self._create_new_group()
    const group_id = new_group.group_id;
    const name = new_group.name;

    _content.groups[group_id] = new_group;

    ce.undo_manager.add_and_exec({
      action: 'add-group-to-section',
      redo() {
        _tree.add_group(group_id, name, {section_id,at_end:true});
      },
      undo() {
        _tree.remove_group(group_id);
        _tree.restore_selection(cur_highlight);
      },
    });
  }

  /**
   * Wraps an existing unbrouped question in a new group
   * @param {number} question_id 
   * @param {number} section_id ID of the section currently containing the question
   * @param {number} section_index index of the question within the containing section
   * @returns {void}
   */
  self.add_group_for_question = function(question_id,section_id,section_index)
  {
    const cur_highlight = _tree.current_selection();

    const new_group = self._create_new_group()
    const group_id = new_group.group_id;
    const group_name = new_group.name;

    _content.groups[group_id] = new_group;

    ce.undo_manager.add_and_exec({
      action:'add-group-for-question',
      redo() {
        _tree.add_group( 
          group_id, 
          group_name, 
          {ref_type:'question', ref_id:question_id, offset:-1}
        );
        _tree.move_to_container('question',question_id,'group',group_id,0);
      },
      undo() {
        _tree.move_to_container(
          'question', question_id,
          'section', section_id, section_index);
        _tree.remove_group(group_id);
      },
    });
  }

  /**
   * Removes a group.  Any questions that were in the group remain
   *   in the containing section, but no longer grouped
   * @param {number} group_id
   * @returns {void}
   */
  self.remove_group = function(group_id)
  {
    alert(`remove_group(${group_id})`);
  }

  // deletion handlers

  /**
   * Removes the specified section element from the survey content and
   *   navigation tree and registers the deletion with the undo manager.
   * @param {jQuery<HTMLLIElement>} $delete_li 
   * @returns {void}
   */
  self.delete_section = function($delete_li) 
  {
    if( $delete_li.length !== 1 ) { return; }
    const section_id = $delete_li.data('item-id');
    const section = _content.sections[section_id];

    const cur_highlight = _tree.current_selection();
    const was_closed = $delete_li.hasClass('closed');

    const where = {};
    const prev = $delete_li.prev();
    if(prev.length) {
      where.section_id = prev.data('item-id');
    }

    const $section_content = $delete_li.find('li.question, li.group');
 
    const undo_tasks = [];
    $section_content.each( function() {
      const $item_li = $(this);
      const item_type = $item_li.data('type');
      const item_id   = $item_li.data('item-id');
      if(item_type === 'group') {
        const group = _content.groups[item_id];
        undo_tasks.push({
          func: _tree.add_group,
          args:[item_id,group.name, {section_id,at_end:true}]
        });
      } else if(item_type === 'question') {
        const question = _content.questions[item_id];
        const $in_group = $item_li.closest('li.group');
        if($in_group.length) {
          const group_id = $in_group.data('item-id');
          undo_tasks.push({
            func: _tree.add_question, 
            args: [item_id, question, { group_id, at_end:true }]
          });
        } else {
          undo_tasks.push({ 
            func: _tree.add_question, 
            args: [item_id, question, { section_id,at_end:true }]
          });
        }
      }
    });
    undo_tasks.push({
      func: _tree.restore_selection,
      args: [cur_highlight]
    });

    ce.undo_manager.add_and_exec({
      action:'delete-section',
      redo() {
        _tree.remove_section(section_id);
      },
      undo() {
        const [tgt_li,tgt_ul] = _tree.add_section(section_id,section.name,where);
        tgt_li.toggleClass('closed',was_closed);
        for(const {func,args} of undo_tasks) {
          func(...args);
        }
      },
    });
  }

  /**
   * Removes the specified group element from the survey content and
   *   navigation tree and registers the deletion with the undo manager.
   * @param {jQuery<HTMLLIElement>} $delete_li 
   * @returns {void}
   */
  self.delete_group = function($delete_li) 
  {
    if( $delete_li.length !== 1 ) { return; }
    const group_id = $delete_li.data('item-id');
    const group    = _content.groups[group_id];

    const $question_lis = $delete_li.find('li.question');
    const question_ids = $question_lis.map(
      function () { return $(this).data('item-id'); } 
    ).get();

    const cur_highlight = _tree.current_selection();
    const was_closed = $delete_li.hasClass('closed');

    const prev = $delete_li.prev();
    const where = {};
    if(prev.length === 1) {
      where.ref_id   = prev.data('item-id');
      where.ref_type = prev.data('type');
      where.offset   = 1;
    } else {
      const $section = $delete_li.closest('li.section');
      where.section_id = $section.data('item-id');
    }

    ce.undo_manager.add_and_exec({
      action: 'delete-group',
      redo() { 
        _tree.remove_group(group_id);
      },
      undo() {
        const [$group_li,$group_ul] = _tree.add_group(group_id, group.name, where);
        $group_li.toggleClass('closed',was_closed);
        question_ids.forEach( function (question_id) {
          _tree.add_question(
            question_id, 
            _content.questions[question_id],
            {group_id, at_end:true},
          )
        });
        _tree.restore_selection(cur_highlight);
      },
    });
  }

  /**
   * Removes the specified question element from the survey content and
   *   navigation tree and registers the deletion with the undo manager.
   * @param {jQuery<HTMLLIElement>} $delete_li 
   * @returns {void}
   */
  self.delete_question = function($delete_li) 
  {
    if( $delete_li.length !== 1 ) { return; }

    const question_id = $delete_li.data('item-id');
    const question = _content.questions[question_id];

    const cur_highlight = _tree.current_selection();

    const $prev = $delete_li.prev();
    const where = {};
    if( $prev.length === 1 ) {
      where.ref_id   = $prev.data('item-id');
      where.ref_type = $prev.data('type');
      where.offset   = 1;
    } else {
      const $container = $delete_li.closest('ul').closest('li');
      if($container.data('type') === 'group') {
        where.group_id = $container.data('item-id');
      } else {
        where.section_id = $container.data('item-id');
      }
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
    if(_$frame.hasClass('section') && _$frame.data('id') === section_id ) { return; }

    _$frame.removeClass('group question').addClass('section').data('id',section_id);

    const section = _content.sections[section_id];
    if(self.editable) { _sectionEditor.show(section_id,section); }
    else              { _sectionViewer.show(section_id,section); }
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
    if(_$frame.hasClass('group') && _$frame.data('id') === group_id ) { return; }

    _$frame.removeClass('section question').addClass('group').data('id',group_id);

    const group = _content.groups[group_id];
    if(self.editable) { _groupEditor.show(group_id,group); }
    else              { _groupViewer.show(group_id,group); }
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
    if(_$frame.hasClass('question') && _$frame.data('id') === question_id ) { return; }

    _$frame.removeClass('section group').addClass('question').data('id',question_id);

    const question = _content.questions[question_id];
    if(self.editable) { _questionEditor.show(question_id,question,_content.options); }
    else              { _questionViewer.show(question_id,question,_content.options); }
    _tree.select_question(question_id);
    _menubar.update_selection();
  }

  /**
   * Unset active selection
   * @returns {void}
   */
  self.clear_selection = function()
  {
    _$frame.removeClass('section group question');
    _$frame.find('div.content-header').text(" ");
    _menubar.update_selection();
  }

  // content methods

  /**
   * Returns a Map of the all content questions not currently in
   *   use in the survey.
   * @returns {Map<number,QuestionInfo>}
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
   * @returns {IntStringMap} 
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

  /**
   * Moves a section to a new location in the tree
   *
   * @param {number} sectionId Unique identifier for the section being moved
   * @param {number} toIndex New position in the tree
   * @returns {boolean} true on success, false on failure
   * @fires SurveyWasReordered on success
   */
  self.move_section = function(section_id,to_index) {
    return _tree.move_section(section_id,to_index);
  }


  /**
   * Moves a group to a new location in a section
   * 
   * @param {number} group_id
   * @param {number} to_section_id
   * @param {number} to_index
   * @returns {boolean}  true on success, false on failure
   * @fires SurveyWasReordered on success
   */
  self.move_group = function(group_id, to_section_id, to_index) {
    return _tree.move_to_container('group', group_id, 'section', to_section_id, to_index);
  }

  /**
   * Moves a question to a new location in a section or group 
   * 
   * @param {number} question_id 
   * @param {"section"|"group"} toType
   * @param {number} toId
   * @param {number} toIndex 
   */
  self.move_question = function(question_id, toType, toId, toIndex) {
    return _tree.move_to_container('question',question_id,toType,toId,toIndex);
  }

  // return controller Object
  return self;
};
