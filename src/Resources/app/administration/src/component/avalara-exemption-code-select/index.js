const { Component, Mixin } = Shopware;
import template from './avalara-exemption-code-select.html.twig';

Component.register('avalara-exemption-code-select', {
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

    inject: ['avalaraExemptionCode'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            exemptionCodeOptions: [],
            localValue: this.value ?? null,
        };
    },

    watch: {
        value(val) {
            this.localValue = val ?? null;
        },
    },

    created() {
        this.fetchExemptionCodes();
    },

    methods: {
        fetchExemptionCodes() {
            this.isLoading = true;

            this.avalaraExemptionCode.list({})
                .then((res) => {
                    this.exemptionCodeOptions = res.exemptionCodes ?? [];
                    if (!res.success) {
                        this.createNotificationError({
                            title: this.$tc('avalara-exemption-code-select.title'),
                            message: this.$tc('avalara-exemption-code-select.error'),
                        });
                    }
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('avalara-exemption-code-select.title'),
                        message: this.$tc('avalara-exemption-code-select.error'),
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
            this.fetchExemptionCodes();
        },
    },
});
