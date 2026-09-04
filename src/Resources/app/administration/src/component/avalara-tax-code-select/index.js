const { Component, Mixin } = Shopware;
import template from './avalara-tax-code-select.html.twig';

Component.register('avalara-tax-code-select', {
    template,

    props: {
        value: {
            type: String,
            default: null,
        },
        label: {
            type: String,
            default: '',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['update:value'],

    inject: ['avalaraTaxCode'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            taxCodeOptions: [],
            localValue: this.value ?? null,
        };
    },

    watch: {
        value(val) {
            this.localValue = val ?? null;
        },
    },

    created() {
        this.fetchTaxCodes();
    },

    methods: {
        fetchTaxCodes(filter = '') {
            this.isLoading = true;

            this.avalaraTaxCode.list({ filter })
                .then((res) => {
                    this.taxCodeOptions = res.taxCodes ?? [];
                    if (!res.success) {
                        this.createNotificationError({
                            title: this.$tc('avalara-tax-code-select.title'),
                            message: this.$tc('avalara-tax-code-select.error'),
                        });
                    }
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('avalara-tax-code-select.title'),
                        message: this.$tc('avalara-tax-code-select.error'),
                    });
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        onSelect(value) {
            this.localValue = value;
            this.$emit('update:value', value);
        },

        onRefresh() {
            this.fetchTaxCodes();
        },
    },
});
