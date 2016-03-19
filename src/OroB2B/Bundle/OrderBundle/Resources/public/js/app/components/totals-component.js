define(function(require) {
    'use strict';

    var TotalsComponent;
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var BaseComponent = require('orob2bpricing/js/app/components/totals-component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');

    /**
     * @export orob2border/js/app/components/totals-component
     * @extends orob2bpricing.app.components.TotalsComponent
     * @class orob2border.app.components.TotalsComponent
     */
    TotalsComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            mediator.on('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.on('entry-point:order:load', this.setTotals, this);
            mediator.on('entry-point:order:load:after', this.hideLoadingMask, this);

            this.$subtotals = this.options._sourceElement.find(this.options.selectors.subtotals);
            this.template = _.template(this.options._sourceElement.find(this.options.selectors.template).text());
            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});

            this.setTotals(options);
        },

        /**
         * @param {Object} data
         */
        setTotals: function(data) {
            var totals = _.defaults(data, {totals: {total: {}, subtotals: {}}}).totals;

            mediator.trigger('entry-point:order:trigger:totals', totals);

            this.render(totals);
        },

        /**
         * @inheritDoc
         */
        updateTotals: function() {
            mediator.trigger('entry-point:order:trigger');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.off('entry-point:order:load', this.setTotals, this);
            mediator.off('entry-point:order:load:after', this.hideLoadingMask, this);

            TotalsComponent.__super__.dispose.call(this);
        }
    });

    return TotalsComponent;
});
