const self = {};

function closed_controller(ce) {
  self.ce = ce;

  return {
    state:'closed',
  }
};

export default closed_controller;
