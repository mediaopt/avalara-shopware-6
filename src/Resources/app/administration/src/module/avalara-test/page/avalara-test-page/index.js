import template from './avalara-test-page.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('avalara-test-page', {
    template,

    inject: [
        'repositoryFactory',
        'AvalaraService',
    ],

    data() {
        return {
            repository: null,
            bundles: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: this.$t('avalara-test.page.columnName'),
                routerLink: 'avalara.test.detail',
                inlineEdit: 'string',
                allowResize: true,
                primary: true
            }, {
                property: 'discount',
                dataIndex: 'discount',
                label: this.$t('avalara-test.page.columnDiscount'),
                inlineEdit: 'number',
                allowResize: true
            }, {
                property: 'discountType',
                dataIndex: 'discountType',
                label: this.$t('avalara-test.page.columnDiscountType'),
                allowResize: true
            }];
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('avalara_test');

        this.repository
            .search(new Criteria(), Shopware.Context.api)
            .then((result) => {
                this.bundles = result;
            });
    },

    methods: {
        onClick() {
            this.AvalaraService.testConnection().then((result) => {
                if(result.result === true) {
                    alert("Connection Successful");
                } else {
                    alert("We got a problem");
                }
            });
        }
    }
});
