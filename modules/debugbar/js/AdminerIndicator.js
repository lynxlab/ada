(function ($) {
    var AdminerIndicator = PhpDebugBar.Widgets.AdminerIndicator = PhpDebugBar.DebugBar.Indicator.extend({

        tagName: 'select',
        className: 'phpdebugbar-datasets-switcher',

        render: function () {
            // AdminerIndicator.__super__.render.apply(this);
            this.bindAttr('data', function (data) {
                this.$el.unbind('change');
                $('option', this.$el).remove();
                if (data.length > 0) {
                    data.forEach(element => {
                        this.$el.append($(`<option value="${element.value ?? ''}">${element.label}</option>`));
                    });
                    this.$el.change(function () {
                        // NOTE: inside the change callback, this is pointing to the <select> element.
                        if ('' != this.value) {
                            const link = document.createElement("a");
                            link.href = this.value;
                            link.target = "_blank";
                            link.click();
                            // Reset the first element as selected
                            $('option:first', this).prop('selected', true).trigger('change');
                        }
                    });
                    this.$el.show();
                } else {
                    this.$el.hide();
                }
            });
        }
    });
})(PhpDebugBar.$);
