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
            repository: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [];
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
                document.getElementById('testResult').innerHTML = result.message;
            });
        }
    }
});
