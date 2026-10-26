const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-customer-detail-base', {
    computed: {
        customFieldSetCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.equals('relations.entityName', 'customer'));
            // Do not pre-load customFields association so that sw-custom-field-set-renderer
            // lazy-loads each set by ID — the same path used by the product page,
            // which correctly includes field configs (componentName, etc.).
            return criteria;
        },
    },
});
