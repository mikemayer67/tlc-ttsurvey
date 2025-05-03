const self = {};

function closed_handler(ce) {
  self.ce = ce;

  return {
    state:'closed',
  }
};

export default closed_handler;
