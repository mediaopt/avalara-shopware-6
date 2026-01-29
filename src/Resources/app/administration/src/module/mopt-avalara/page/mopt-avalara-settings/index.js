import template from './mopt-avalara-settings.html.twig';

const { Component } = Shopware;

Component.register('mopt-avalara-settings', {
    template,

    inject: ['avalaraOrderStates'],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            salesChannelId:null,
            cancelStatusId: '',
            refundStatusId: '',
            selectOptions: '',
        };
    },

    created() {
        this.getInitialData();
    },

    methods: {
        onSave() {
            this.isLoading = true;
            this.$refs.systemConfig.saveAll().then(() => {
                this.$refs.systemConfig.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: this.$tc('sw-settings-search.notification.saveSuccess')
                });

                this.isLoading = false;
            }).catch((err) => {
                this.$refs.systemConfig.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: err
                });

                this.isLoading = false;
            });
        },

        onConfigChanged() {
            this.salesChannelId = this.$refs.systemConfig.currentSalesChannelId ?? "null";

            let cancelId = this.cancelStatusId;
            if(
                this.$refs.systemConfig.actualConfigData
                && this.$refs.systemConfig.actualConfigData[this.salesChannelId]
                && this.$refs.systemConfig.actualConfigData[this.salesChannelId]["MoptAvalara6.config.orderCancel"]
            ) {
                cancelId = this.$refs.systemConfig.actualConfigData[this.salesChannelId]["MoptAvalara6.config.orderCancel"];
            }

            this.cancelStatusId = cancelId;

            let refundId = this.refundStatusId;
            if(
                this.$refs.systemConfig.actualConfigData
                && this.$refs.systemConfig.actualConfigData[this.salesChannelId]
                && this.$refs.systemConfig.actualConfigData[this.salesChannelId]["MoptAvalara6.config.orderRefund"]
            ) {
                refundId = this.$refs.systemConfig.actualConfigData[this.salesChannelId]["MoptAvalara6.config.orderRefund"];
            }

            this.refundStatusId = refundId;

        },

        getInitialData() {
            this.loading = true;
            this.avalaraOrderStates.getStates({localeId: Shopware.State.get('session').currentUser.localeId})
                .then((res) => {
                    this.cancelStatusId = res.cancelStatusId;
                    this.refundStatusId = res.refundStatusId;
                    this.selectOptions = res.selectOptions;
                })
                .finally(() => {
                    this.loading = false;
                })
            ;
        },

        setCancelStatusId(value) {
            this.loading = true;
            this.cancelStatusId = value;
            this.$refs.systemConfig.actualConfigData[this.salesChannelId]["MoptAvalara6.config.orderCancel"] = value;
        },

        setRefundStatusId(value) {
            this.loading = true;
            this.refundStatusId = value;
            this.$refs.systemConfig.actualConfigData[this.salesChannelId]["MoptAvalara6.config.orderRefund"] = value;
        },

    }
});
