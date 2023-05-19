const { Component, Mixin } = Shopware;
import template from './avalara-api-test-button.html.twig';

Component.register('avalara-api-test-button', {
    template,

    props: ['label'],
    inject: ['avalaraApiTest'],

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
            this.avalaraApiTest.check(this.pluginConfig).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('avalara-api-test-button.title'),
                        message: this.$tc('avalara-api-test-button.success')
                    });
                    document.querySelector('.sw-extension-config__save-action').click();
                } else {
                    this.createNotificationError({
                        title: this.$tc('avalara-api-test-button.title'),
                        message: this.$tc('avalara-api-test-button.error')
                    });
                }

                this.isLoading = false;
            });
        }
    }
})
