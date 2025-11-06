export default function undo_manager(ce)
{
  const MAX_UNDO_DEPTH = 1000;
  const MAX_REVERT_DEPTH = 25;

  const _undo_stack = [];
  const _redo_stack = [];

  let _overflow = false;

  let next_uid=1;

  function add(a,exec) {
    a.uid = next_uid++;

    // - the action a must support both the undo() and redo() methods
    //
    // - if the exact same action is pushed onto the stack multiple times
    //     in a row, only once instance will be added

    if(_redo_stack.length > 0) {
      _undo_stack.push(a);
      _redo_stack.length = 0;
    } else if( a !== _undo_stack.at(-1) ) {
      _undo_stack.push(a);
    }

    while(_undo_stack.length > MAX_UNDO_DEPTH ) { 
      _overflow = true;
      _undo_stack.shift(); 
    }

    if(exec) { a.redo(); }
    notify();
  }

  function pop(a) {
    // if the provided action is the last on the undo stack, and the redo stack is
    //   empty, remove the provided action from the undo stack.
    if( _undo_stack.at(-1) !== a  && _redo_stack.length == 0) { return false; }

    _undo_stack.pop(); 
    notify();
    return true;
  }

  function redo() {
    const a = _redo_stack.pop();
    if(!a) { return false; }
    // don't need to worry about undo depth as redo must have once fit on the undo stack
    _undo_stack.push(a);
    a.redo();
    notify();
    return true;
  }

  function undo() {
    const a = _undo_stack.pop();
    if(!a) { return false; }

    _redo_stack.push(a);
    a.undo();
    notify();
    return true;
  }

  function head() {
    // Returns the top of the undo stack, but only if the redo stack is empty
    if(_redo_stack.length  >  0 ) { return null; }
    if(_undo_stack.length === 0 ) { return null; }
    return _undo_stack.at(-1);
  }

  function revert() {
    // Note that this returns true if the undo stack is empty
    if( _undo_stack.length > MAX_REVERT_DEPTH ) { return false; }
    while(_undo_stack.length > 0) {
      _undo_stack.pop().undo();
    }
    _redo_stack.length = 0;
    _overflow = false;
    notify();
    return true;
  }

  function empty() {
    _overflow = false;
    _undo_stack.length = 0;
    _redo_stack.length = 0;
    notify();
  }

  function notify() {
    $(document).trigger('UndoStackChanged',{
      undo_count: _undo_stack.length,
      redo_count: _redo_stack.length,
    });
  }

  $(document).on('keydown', function(e) {
    const ctrlOrCmd = ce.isMac ? e.metaKey : e.ctrlKey;
    if (ctrlOrCmd && e.key.toLowerCase() === 'z' && !e.shiftKey) {
      e.preventDefault();
      undo();
    }
    if (
      (ctrlOrCmd && e.key.toLowerCase() === 'z' && e.shiftKey) ||
      (!ce.isMac && e.ctrlKey && e.key.toLowerCase() === 'y')
    ) {
      e.preventDefault();
      redo();
    }
  });

  return {
    add(a)          { add(a,false); },
    add_and_exec(a) { add(a,true);  },
    hasUndo()       { return _undo_stack.length > 0; },
    hasRedo()       { return _redo_stack.length > 0; },
    pop, redo, undo, revert, empty, head,
  };
}
