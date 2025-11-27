const { Component, Mixin } = Shopware;
import template from './avalara-order-statuses.html.twig';

Component.register('avalara-order-statuses', {
    template,
    inject: ['avalaraApiTest'],

    mixins: [
        Mixin.getByName('notification')
    ],

    mounted() {
        this.avalaraApiTest.getOptionsStates() .then((res) => {
            if (res.success) {
                console.log(res.success);
            } else {
                console.log(res);
            }
        });

        console.log('tests!');
    }
})
