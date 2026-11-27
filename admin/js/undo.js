/**
 * @typedef {Object} UndoableAction
 * @property {() => void } undo
 * @property {() => void } redo
 * @property {number} [uid] ID added by the UndoManager
 * @property {string} [action] descriptor (not used by Undo Manager)
 */

/**
 * @typedef {Object} UndoStackChangedData
 * Data passed as an additional argument with the UndoStackChanged event
 * @property {number} undo_count
 * @property {number} redo_count
 */

/**
 * @typedef {Object} UndoManager
 * @property { (UndoableAction) => void } add Adds an action to the undo stack
 * @property { (UndoableAction) => void } add_and_exec Adds an action and immediately invokes its redo method
 * @property { () => boolean } hasUndo
 * @property { () => boolean } hasRedo
 * @property { (UndoableAction) => boolean } pop
 * @property { () => boolean } redo
 * @property { () => boolean } undo
 * @property { () => null|UndoableAction } head
 * @property { () => boolean } revert
 * @property { () => void } empty
 */

/**
 * Undo Manager constructor 
 * @param {RuntimeContext} ce 
 * @returns {UndoManager}
 */
export default function undo_manager(ce)
{
  const MAX_UNDO_DEPTH = 1000;
  const MAX_REVERT_DEPTH = 25;

  const _undo_stack = [];
  const _redo_stack = [];

  let _overflow = false;

  let next_uid=1;

  /**
   * Adds a new action to the undo stack.
   *   If exec is set to true, invokes the undo method immediately
   * @note The addition of a new undoable action clears the redo stack
   * @note If the maximum undo stack depth is exceeded, the oldest action will be dropped from the stack
   * @note If the same UndoaableAction object is added twice in a row, only one will be added to the stack.
   * @param {UndoableAction} a
   * @param {boolean} exec 
   * @fires UndoStackChanged on $(document) with additional UndoStackChangedData event data
   */
  function add(a,exec) {
    a.uid = next_uid++;

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

  /**
   * Removes the specified action from the undo stack if:
   *   - It is the newest action on the stack
   *   - The redo stack is empty
   * @param {UndoableAction} a 
   * @return {boolean} success
   * @fires UndoStackChanged on $(document) with additional UndoStackChangedData event data
   */
  function pop(a) {
    // if the provided action is the last on the undo stack, and the redo stack is
    //   empty, remove the provided action from the undo stack.
    if( _undo_stack.at(-1) !== a ) { return false; }
    if( _redo_stack.length > 0)    { return false; }

    _undo_stack.pop(); 
    notify();
    return true;
  }

  /**
   * Executes the redo action at the top of the redo stack and moves
   *   the action to the top of the undo stack
   * @fires UndoStackChanged on $(document) with additional UndoStackChangedData event data
   */
  function redo() {
    const a = _redo_stack.pop();
    if(!a) { return false; }
    // don't need to worry about undo depth as redo must have once fit on the undo stack
    _undo_stack.push(a);
    a.redo();
    notify();
    return true;
  }

  /**
   * Executes the undo action at the top of the undo stack and moves
   *   the action to the top of the redo stack
   * @fires UndoStackChanged on $(document) with additional UndoStackChangedData event data
   */
  function undo() {
    const a = _undo_stack.pop();
    if(!a) { return false; }

    _redo_stack.push(a);
    a.undo();
    notify();
    return true;
  }

  /**
   * Returns the top of the undo stack, but only if the redo stack is empty
   * @returns {null|UndoableAction}
   */
  function head() {
    // Returns the top of the undo stack, but only if the redo stack is empty
    if(_redo_stack.length  >  0 ) { return null; }
    if(_undo_stack.length === 0 ) { return null; }
    return _undo_stack.at(-1);
  }

  /**
   * Executes all actions on the undo stack from newest to oldest
   * @note will only run if the maximum revert depth has not been exceeded (currently 25 actions).
   * @note both the undo and redo stacks will be empty on success
   * @returns {boolean} success
   * @fires UndoStackChanged on $(document) with additional UndoStackChangedData event data
   */
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

  /**
   * Clears both the undo and redo stacks without executing any action from
   *   either stack.
   * @fires UndoStackChanged on $(document) with additional UndoStackChangedData event data
   */
  function empty() {
    _overflow = false;
    _undo_stack.length = 0;
    _redo_stack.length = 0;
    notify();
  }

  /**
   * Sends an UndoStackChanged event to $(document)
   * @fires UndoStackChanged on $(document) with additional UndoStackChangedData event data
   */
  function notify() 
  {
    //--------------------------------------------------------------------------------
    // Uncomment the following to debug use of the undo manager
    //--------------------------------------------------------------------------------
    // console.log("undo: " + String(_undo_stack.length) + "  redo: " + _redo_stack.length);
    // if(_undo_stack.length>0 && Object.hasOwn(_undo_stack.at(-1),'action')) {
    //   console.log('  next undo: ' + _undo_stack.at(-1).action);
    // }
    // if(_redo_stack.length>0 && Object.hasOwn(_redo_stack.at(-1),'action')) {
    //   console.log('  next redo: ' + _redo_stack.at(-1).action);
    // }
    //--------------------------------------------------------------------------------
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
