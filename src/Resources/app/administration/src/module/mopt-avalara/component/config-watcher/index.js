import template from './config-watcher.html.twig';

const { Component } = Shopware;

Component.register('mopt-avalara-config-watcher', {
    template,

    props: {
        config: {
            type: Object,
            required: true,
            default() {
                return {};
            }
        },
    },

    watch: {
        config: {
            handler(newConfig) {
                this.$emit('config-changed', {
                    config: newConfig,
                    instanceId: this.instanceId
                });
            },
            deep: true
        }
    }
});
