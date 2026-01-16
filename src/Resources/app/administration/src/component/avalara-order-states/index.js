const { Component, Mixin } = Shopware;
import template from './avalara-order-states.html.twig';

Component.register('avalara-order-states', {
    template,

    props: ['label'],
    inject: ['avalaraOrderStates'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            cancelStatusId: '',
            refundStatusId: '',
            selectOptions: '',
        };
    },

    created() {
        this.$watch('paymentMethod', (pM) => {
            this.currentPaymentMethodId = pM.customFields.worldline_payment_method_id;
        });
        this.getInitialData();
    },


    methods: {
        getInitialData() {
            this.loading = true;
            this.avalaraOrderStates.getStates({languageId: Shopware.Context.api.languageId})
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
            this.avalaraOrderStates.setCancelStatusId({cancelStatusId: this.cancelStatusId})
                .then((res) => {
                })
                .finally(() => {
                    this.loading = false;
                })
            ;
        },

        setRefundStatusId(value) {
            this.loading = true;
            this.refundStatusId = value;
            this.avalaraOrderStates.setRefundStatusId({refundStatusId: this.refundStatusId})
                .then((res) => {
                })
                .finally(() => {
                    this.loading = false;
                })
            ;
        },
    },
})
