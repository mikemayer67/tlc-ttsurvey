const self = {};

function active_handler(ce) {
  self.ce = ce;

  return {
    state:'active',
  }
};

export default active_handler;
