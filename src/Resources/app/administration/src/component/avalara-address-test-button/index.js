const { Component, Mixin } = Shopware;
import template from './avalara-address-test-button.html.twig';

Component.register('avalara-address-test-button', {
    template,

    props: ['label'],
    inject: ['avalaraAddressTest'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    computed: {
        pluginConfig() {
            let $parent = this.$parent;

            while ($parent.actualConfigData === undefined) {
                $parent = $parent.$parent;
            }

            return $parent.actualConfigData.null;
        }
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },

        check() {
            this.isLoading = true;
            this.avalaraAddressTest.check(this.pluginConfig).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('avalara-address-test-button.title'),
                        message: this.$tc('avalara-address-test-button.success') + res.message
                    });
                    document.querySelector('.sw-extension-config__save-action').click();
                } else {
                    this.createNotificationError({
                        title: this.$tc('avalara-address-test-button.title'),
                        message: this.$tc('avalara-address-test-button.error') + res.message
                    });
                }

                this.isLoading = false;
            });
        }
    }
})
