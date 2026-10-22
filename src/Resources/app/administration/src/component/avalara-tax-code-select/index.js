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
            lastSearchTerm: '',
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
        getNoTaxCodeOption() {
            return { value: null, label: this.$tc('avalara-tax-code-select.noTaxCode') };
        },

        fetchTaxCodes(filter = '') {
            this.isLoading = true;

            this.avalaraTaxCode.list({ filter })
                .then((res) => {
                    if (!res.success) {
                        this.createNotificationError({
                            title: this.$tc('avalara-tax-code-select.title'),
                            message: this.$tc('avalara-tax-code-select.error'),
                        });
                    }

                    const taxCodes = res.taxCodes ?? [];

                    // On initial load (no active filter), ensure the saved value is present in the options.
                    // The API returns at most 500 results; a previously saved code may fall outside that window.
                    if (!filter && this.localValue) {
                        const alreadyPresent = taxCodes.some(opt => opt.value === this.localValue);
                        if (!alreadyPresent) {
                            return this.avalaraTaxCode.list({ filter: this.localValue })
                                .then((savedRes) => {
                                    const savedOption = (savedRes.taxCodes ?? []).find(opt => opt.value === this.localValue);
                                    this.taxCodeOptions = [
                                        this.getNoTaxCodeOption(),
                                        ...(savedOption ? [savedOption] : []),
                                        ...taxCodes,
                                    ];
                                });
                        }
                    }

                    this.taxCodeOptions = [this.getNoTaxCodeOption(), ...taxCodes];
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

        onSearch(term) {
            this.lastSearchTerm = term;
        },

        onRefresh() {
            const term = this.lastSearchTerm;
            this.closeResultList();
            this.fetchTaxCodes(term);
        },

        closeResultList() {
            const select = this.$refs.taxCodeSelect;
            if (!select) {
                return;
            }

            if (typeof select.closeResultList === 'function') {
                select.closeResultList();
            } else if (select.$refs?.selectBase && typeof select.$refs.selectBase.collapse === 'function') {
                select.$refs.selectBase.collapse();
            }
        },
    },
});
