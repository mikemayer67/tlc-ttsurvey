// Adds helper functions to accomplish common jquery attribute and property setters

$.fn.extend({
  setId(id)                 { return this.attr('id',id); },
  setType(type)             { return this.attr('type', type); },
  placeholder(text)         { return this.attr('placeholder', text); },
  disable(state=true)       { return this.prop('disabled', state).toggleClass('disabled',state); },
  enable()                  { return this.prop('disabled', false).removeClass('disabled'); },
  autocomplete(token)       { return this.attr('autocomplete', token); },
  isChecked()               { return this.prop('checked'); },
  setChecked(state=true)    { return this.prop('checked',state); },
  setUnchecked()            { return this.prop('checked',false); },
  toggleChecked(state=null) { 
    return this.prop('checked', state!==null ? state : !this.prop('checked'));
  },

});
