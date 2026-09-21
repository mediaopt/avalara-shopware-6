const { Component, Mixin } = Shopware;
import template from './avalara-company-code-select.html.twig';

Component.register('avalara-company-code-select', {
    template,

    props: ['label'],
    inject: ['avalaraCompanyCode'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            companyOptions: [],
        };
    },

    computed: {
        systemConfigParent() {
            let $parent = this.$parent;
            while ($parent && $parent.actualConfigData === undefined) {
                $parent = $parent.$parent;
            }
            return $parent;
        },

        currentSalesChannelId() {
            return this.systemConfigParent?.currentSalesChannelId ?? 'null';
        },

        selectedCode: {
            get() {
                const parent = this.systemConfigParent;
                if (!parent?.actualConfigData) {
                    return '';
                }
                const channelId = this.currentSalesChannelId;
                return parent.actualConfigData?.[channelId]?.['MoptAvalara6.config.companyCode']
                    ?? parent.actualConfigData?.['null']?.['MoptAvalara6.config.companyCode']
                    ?? '';
            },
            set(value) {
                const parent = this.systemConfigParent;
                if (!parent?.actualConfigData) {
                    return;
                }
                const channelId = this.currentSalesChannelId;
                if (!parent.actualConfigData[channelId]) {
                    parent.actualConfigData[channelId] = {};
                }
                parent.actualConfigData[channelId]['MoptAvalara6.config.companyCode'] = value;
            }
        }
    },

    created() {
        this.fetchCompanies();
    },

    methods: {
        fetchCompanies() {
            this.isLoading = true;
            const salesChannelId = this.currentSalesChannelId;

            this.avalaraCompanyCode.list({ salesChannelId })
                .then((res) => {
                    this.companyOptions = res.companies ?? [];
                    if (!res.success) {
                        this.createNotificationError({
                            title: this.$tc('avalara-company-code-select.title'),
                            message: this.$tc('avalara-company-code-select.error'),
                        });
                    }
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('avalara-company-code-select.title'),
                        message: this.$tc('avalara-company-code-select.error'),
                    });
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        onSelect(value) {
            this.selectedCode = value;
        },
    }
});