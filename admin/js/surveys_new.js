const self = {};

function new_handler(ce) {
  self.ce = ce;

  return {
    state:'new',
  };
};

export default new_handler;
