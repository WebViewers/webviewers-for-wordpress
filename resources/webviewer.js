;(function($, rJS, window) {
  rJS(window).ready(function() {
    var root = rJS(this);
    $('[data-gadget]').each(function(_, elem) {
      root.declareIframedGadget($(elem).attr('data-gadget'), $(elem)).done(function(gadget) {
        gadget.getInterfaceList().done(function(list) {
            if (list.indexOf('http://www.renderjs.org/interface/blob-editor') > -1) {
                gadget.setContent($(elem).attr('data-gadget-content'));
            } else if (list.indexOf('http://www.renderjs.org/interface/text-editor') > -1) {
                $.ajax($(elem).attr('data-gadget-content')).done(function(resp) {
                    gadget.setContent(resp);
                });
            } else {
                console.log('gadget does not implement any standard form');
            }
        });
      });
    });
  });
})(jQuery, rJS, window);
