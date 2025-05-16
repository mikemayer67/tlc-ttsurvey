import Sortable from '../../js/sortable.esm.js';

export default function survey_editor(ce)
{
  const _content_editor  = $('#content-editor');
  const _tree_box        = _content_editor.find('#survey-tree');
  const _tree_info       = _tree_box.find('.info');
  const _survey_tree     = _tree_box.find('ul.sections');
  const _element_editor  = _content_editor.find('#element-editor');
  const _resizer         = _content_editor.find('.resizer');

  let _editable = false;
  let _content = null;

  // editor pane resizing

  var _resize_data = null;

  function start_resize(e) {
    e.preventDefault();
    _content_editor.css('cursor','col-resize');
    _resize_data = { 
      min_x : 200 - _tree_box.width(),
      max_x : _element_editor.width() - 300,
      start_x : e.pageX,
      start_w : _tree_box.width(),
      in_editor : true,
      last_move : 0,
    };
    _content_editor.on('mouseenter', function(e) { _resize_data.in_editor = true;  } );
    _content_editor.on('mouseleave', function(e) { _resize_data.in_editor = false; } );
    $(document).on('mousemove',track_mouse);
    $(document).on('mouseup',stop_tracking_mouse);
  }

  function track_mouse(e) {
    e.preventDefault();
    if(_resize_data) {
      const now = Date.now();
      if( now > _resize_data.last_move + 10 ) {
       _resize_data.last_move = now;

       const dx = e.pageX - _resize_data.start_x;
       if( dx > _resize_data.min_x && dx < _resize_data.max_x ) {
         _tree_box.width(_resize_data.start_w + dx);
       }
      }
    }
  }

  function stop_tracking_mouse(e) {
    e.preventDefault();
    if(_resize_data) {
      _content_editor.css('cursor','');
      if(!_resize_data.in_editor) { 
        _tree_box.width(_resize_data.start_w); 
      }
      _content_editor.off('mouseenter');
      _content_editor.off('mouseleave');
      _resize_data = null;
    }
    $(document).off('mousemove',track_mouse);
    $(document).off('mouseup',stop_tracking_mouse);
  }

  _resizer.on('mousedown',start_resize);

  // editor content

  function update_content(survey_id) {
    console.log('update all content');
    const survey = ce.survey_data.lookup(survey_id);
    const content = ce.survey_data.content(survey_id);

    reset_survey_tree();

    if(!content) { _content=null; return; }
    _content = content;

    // Add sections to the survey tree

    Object.keys(content.sections)
    .map(Number)
    .sort((a,b) => a-b)
    .forEach( sid => { // section id
      const section = content.sections[sid];

      const btn = $('<button>').addClass('toggle');
      const span = $('<span>').text(section.name).addClass('name');
      const div = $('<div>').append(btn,span);

      const li = $('<li>')
        .addClass('section closed')
        .attr('data-section',sid)
        .html(div)
        .appendTo(_survey_tree);

      const ul = $('<ul>').addClass('elements').appendTo(li);

      btn.on('click', function(e) {
        const li = $(this).parent().parent();
        if( li.hasClass('closed') ) { li.removeClass('closed'); }
        else                        { li.addClass('closed');    }
      });
      span.on('click', function(e) {
        _survey_tree.find('.selected').removeClass('selected');
        $(this).parent().addClass('selected');
        const li = $(this).parent().parent();
        const sid = li.data('section');
        console.log('selected section: ' + content.sections[sid].name);
      });

      Object.entries(content.elements)
      .filter( ([eid,element]) => element.section == sid )
      .sort( ([aid,a],[bid,b]) => a.sequence - b.sequence )
      .forEach( ([eid,element]) => {

        const eli = $('<li>')
        .addClass('element')
        .addClass(element.type.toLowerCase())
        .attr('data-element',eid)
        .text(element.label);
        if(element.multiple) { 
          eli.addClass('multi'); 
        }
        eli.appendTo(ul);
        eli.on('click',function(e) {
          _survey_tree.find('.selected').removeClass('selected');
          $(this).addClass('selected');
          const eid = $(this).data('element');
          console.log('selected element: ' + content.elements[eid].label + ' (' + content.elements[eid].type + ')');
        });

      });
    });

    // Tree sorting
    if( _editable ) {
      setup_tree_sorting();
      enable_tree_sorting();
    }
  }

  $(document).on('NewContentData', function(e,survey_id) {
    update_content(survey_id);
  });

  // update handler

//  function send_change_notification(what,info)
//  {
//    if(info !== undefined) {
//      $(document).trigger('ContentModifiedByUser',{what:what,info:info});
//    } else {
//      $(document).trigger('ContentModifiedByUser',{what:what});
//    }
//  }
//  
//  function handle_user_updates(info)
//  {
//    console.log('handle user updates: ' + info.what);
//  }
//
//  $(document).on('ContentModifiedByUser', function(e,info) {
//    handle_user_updates(info);
//  });

  // survey tree 

  let _element_sorters = [];

  const _section_sorter = new Sortable(
    _survey_tree[0],
    {
      group: 'sections',
      animation: 150,
      filter: '.element',
      onEnd: handle_drop_section,
    }
  );

  function reset_survey_tree()
  {
    _section_sorter.option('disabled',true);
    _element_sorters.forEach( (s) => s.destroy() );
    _element_sorters = [];
    _survey_tree.empty();
    _tree_info.hide();
  }

  function setup_tree_sorting()
  {
    _section_sorter.option('disabled',false);
    _survey_tree.find('ul.elements').each( function() {
      const sorter = new Sortable(
        this,
        {
          group: { name:'elements', pull:true, pust:true },
          animation: 150,
          onEnd: handle_drop_element,
        }
      );
      _element_sorters.push(sorter);
    });
  }

  function handle_drop_section(e)
  {
    _survey_tree.find('.drop-target').removeClass('drop-target');

    if(e.oldIndex === e.newIndex) { return false; }

    const action = {
      element: e.item,
      oldIndex: e.oldIndex,
      newIndex: e.newIndex,
      undo() {
        const st = _survey_tree[0];
        const peers = st.children;
        var tgt_index = this.oldIndex;
        if( tgt_index > this.newIndex ) { tgt_index +=1; }
        if( tgt_index >= peers.length ) {
          console.log('undo append');
          st.appendChild(this.element);
        } else {
          console.log('undo insert at ' + tgt_index);
          st.insertBefore(this.element, peers[tgt_index]);
        }
      },
      redo() {
        const st = _survey_tree[0];
        const peers = st.children;
        var tgt_index = this.newIndex;
        if( tgt_index > this.oldIndex ) { tgt_index +=1; }
        if( tgt_index >= peers.length ) {
          console.log('undo append');
          st.appendChild(this.element);
        } else {
          console.log('redo insert at ' + tgt_index);
          st.insertBefore(this.element, peers[tgt_index]);
        }
      },
    };

    ce.undo_manager.add(action);

    return true;
  }

  function handle_drop_element(e)
  {
    _survey_tree.find('.drop-target').removeClass('drop-target');

    if(e.from === e.to && e.oldIndex === e.newIndex) { return false; }

    const action = {
      element: e.item,
      from: e.from,
      to: e.to,
      oldIndex: e.oldIndex,
      newIndex: e.newIndex,
      undo() {
        var tgt_index = this.oldIndex;
        if( tgt_index > this.newIndex ) { tgt_index +=1; }
        const peers = this.from.children;
        if( tgt_index >= peers.length ) {
          console.log('undo append');
          this.from.appendChild(this.element);
        } else {
          console.log('undo insert at ' + tgt_index);
          this.from.insertBefore(this.element, peers[tgt_index]);
        }
      },
      redo() {
        var tgt_index = this.newIndex;
        if( tgt_index > this.oldIndex ) { tgt_index +=1; }
        const peers = this.to.children;
        if( tgt_index >= peers.length ) {
          console.log('redo append');
          this.to.appendChild(this.element);
        } else {
          console.log('redo insert at ' + tgt_index);
          this.to.insertBefore(this.element, peers[tgt_index]);
        }
      },
    };

    ce.undo_manager.add(action);

    return true;
  }

  function disable_tree_sorting()
  {
    _tree_info.hide();
    _section_sorter.option('disabled',true);
    _element_sorters.forEach( (s) => s.option('disabled',true) );
  }

  function enable_tree_sorting()
  {
    _tree_info.show();
    _section_sorter.option('disabled',false);
    _element_sorters.forEach( (s) => s.option('disabled',false) );
  }


  // return editor object

  return {
    show() { _content_editor.show(); },
    hide() { _content_editor.hide(); },
    disable() { _editable = false },
    enable() { _editable = true },
    update_content: update_content,
  }
};
