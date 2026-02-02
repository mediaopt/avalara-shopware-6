const { Component, Mixin } = Shopware;
import template from './support-form.html.twig';
import '../../assets/support-form.css';

Component.register('support-form', {
    template,

    props: ['label'],
    inject: ['avalaraSupportForm'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            createAccountCheck: true,
            attachLogCheck: true,
            emailField: '',
            descriptionField: '',
            emailPlaceholder: '',
            createUserFormIsDisabled: true,
            createUserHelpText: this.$tc('avalara-support-form.user-have-no-rights'),
        };
    },

    created() {
        this.checkUserRights();
    },

    methods: {
        checkUserRights() {
            this.avalaraSupportForm.checkUserRights(
            ).then((res) => {
                if (res.createUser) {
                    this.createUserFormIsDisabled = false;
                    this.createUserHelpText = this.$tc('avalara-support-form.create-account-help');
                }
                this.emailPlaceholder = res.userEmail;
                this.isLoading = false;
            });
        },
        send() {
            this.avalaraSupportForm.send(
                {
                    'createAccount': this.createAccountCheck,
                    'attachLog': this.attachLogCheck,
                    'contact': this.emailField,
                    'description': this.descriptionField,
                }
            ).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('avalara-support-form.title'),
                        message: this.$tc('avalara-support-form.success')
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('avalara-support-form.title'),
                        message: this.$tc('avalara-support-form.error') + res.message
                    });
                }

                this.isLoading = false;
            });
        },
        downloadLog() {
            this.avalaraSupportForm.downloadLog(
            ).then((response) => {
                var url = window.location.origin + '/'+response.mediaUrl;
                const link = document.createElement("a");
                link.download = response.mediaName;
                link.href = url;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        },
    }
})
