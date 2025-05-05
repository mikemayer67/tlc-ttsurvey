const self = {};

function active_controller(ce) {
  self.ce = ce;

  return {
    state:'active',
  }
};

export default active_controller;
