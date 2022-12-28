pimcore.registerNS("pimcore.plugin.PresentationBundle");

pimcore.plugin.PresentationBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.PresentationBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        // alert("PresentationBundle ready!");
    }
});

var PresentationBundlePlugin = new pimcore.plugin.PresentationBundle();
