export default function survey_editor(ce)
{
  const _content_editor  = $('#content-editor');
  const _tree_box     = _content_editor.find('#survey-tree');
  const _survey_tree     = _tree_box.find('ul.sections');
  const _element_editor  = _content_editor.find('#element-editor');
  const _resizer         = _content_editor.find('.resizer');

  let _editable = false;

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

  function update_content(content) {
    console.log('update all content');
    _survey_tree.remove('li');

    if(!content) { return; }

    for(const [section_index,section] of content.entries()) {
      const name = section.name ?? 'Unlabeled Header';
      const btn = $('<button>').addClass('toggle');
      const span = $('<span>').text(name).addClass('name');
      const div = $('<div>').append(btn,span);

      btn.on('click', function(e) {
        const li = $(this).parent().parent();
        if( li.hasClass('closed') ) { li.removeClass('closed'); }
        else                        { li.addClass('closed');    }
      });

      if(!section.name) { span.addClass('header'); }

      const li = $('<li>')
        .addClass('section closed')
        .attr('data-section',section_index)
        .html(div)
        .appendTo(_survey_tree);
      if(section.elements) {
        const ul = $('<ul>').appendTo(li);
        for(const [element_index,element] of section.elements.entries()) {
          const eli = $('<li>')
            .addClass('element')
            .addClass(element.type.toLowerCase())
            .attr('data-section',section_index)
            .attr('data-element',element_index)
            .text(element.label)
            .appendTo(ul);
          if(element.multiple) { 
            eli.addClass('multi'); 
          }
        }
      }
    }
  }
  $(document).on('NewContentData', function(e,data) {
    update_content(data);
  });

  // survey tree

  return {
    show() { _content_editor.show(); },
    hide() { _content_editor.hide(); },
    disable() { _editable = false },
    enable() { _editable = true },
    update_content: update_content,
  }
};
