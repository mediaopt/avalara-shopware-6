(function(){var t={641:function(){let t=Shopware.Classes.ApiService,{Application:e}=Shopware;class s extends t{constructor(t,e,s="avalara-address-test"){super(t,e,s)}check(e){let s=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/test-address`,e,{headers:s}).then(e=>t.handleResponse(e))}}e.addServiceProvider("avalaraAddressTest",t=>new s(e.getContainer("init").httpClient,t.loginService))},483:function(){let t=Shopware.Classes.ApiService,{Application:e}=Shopware;class s extends t{constructor(t,e,s="avalara-api-test"){super(t,e,s)}check(e){let s=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/test-connection`,e,{headers:s}).then(e=>t.handleResponse(e))}}e.addServiceProvider("avalaraApiTest",t=>new s(e.getContainer("init").httpClient,t.loginService))}},e={};function s(a){var i=e[a];if(void 0!==i)return i.exports;var n=e[a]={exports:{}};return t[a](n,n.exports,s),n.exports}s.p="bundles/moptavalara6/",window?.__sw__?.assetPath&&(s.p=window.__sw__.assetPath+"/bundles/moptavalara6/"),function(){"use strict";s(483);let{Component:t,Mixin:e}=Shopware;t.register("avalara-api-test-button",{template:'<div>\n    <sw-button-process\n        :isLoading="isLoading"\n        :processSuccess="isSaveSuccessful"\n        @process-finish="saveFinish"\n        @click="check"\n    >{{ $tc(\'avalara-api-test-button.button\') }}</sw-button-process>\n</div>\n',props:["label"],inject:["avalaraApiTest"],mixins:[e.getByName("notification")],data(){return{isLoading:!1,isSaveSuccessful:!1}},computed:{pluginConfig(){let t=this.$parent;for(;void 0===t.actualConfigData;)t=t.$parent;return t.actualConfigData.null}},methods:{saveFinish(){this.isSaveSuccessful=!1},check(){this.isLoading=!0,this.avalaraApiTest.check(this.pluginConfig).then(t=>{t.success?(this.isSaveSuccessful=!0,this.createNotificationSuccess({title:this.$tc("avalara-api-test-button.title"),message:this.$tc("avalara-api-test-button.success")}),document.querySelector(".sw-extension-config__save-action").click()):this.createNotificationError({title:this.$tc("avalara-api-test-button.title"),message:this.$tc("avalara-api-test-button.error")}),this.isLoading=!1})}}}),s(641);let{Component:a,Mixin:i}=Shopware;a.register("avalara-address-test-button",{template:'<div>\n    <sw-button-process\n        :isLoading="isLoading"\n        :processSuccess="isSaveSuccessful"\n        @process-finish="saveFinish"\n        @click="check"\n    >{{ $tc(\'avalara-address-test-button.button\') }}</sw-button-process>\n</div>\n',props:["label"],inject:["avalaraAddressTest"],mixins:[i.getByName("notification")],data(){return{isLoading:!1,isSaveSuccessful:!1}},computed:{pluginConfig(){let t=this.$parent;for(;void 0===t.actualConfigData;)t=t.$parent;return t.actualConfigData.null}},methods:{saveFinish(){this.isSaveSuccessful=!1},check(){this.isLoading=!0,this.avalaraAddressTest.check(this.pluginConfig).then(t=>{t.success?(this.isSaveSuccessful=!0,this.createNotificationSuccess({title:this.$tc("avalara-address-test-button.title"),message:this.$tc("avalara-address-test-button.success")+t.message}),document.querySelector(".sw-extension-config__save-action").click()):this.createNotificationError({title:this.$tc("avalara-address-test-button.title"),message:this.$tc("avalara-address-test-button.error")+t.message}),this.isLoading=!1})}}});var n=JSON.parse('{"avalara-api-test-button":{"title":"API Test","success":"Verbindung wurde erfolgreich getestet","error":"Verbindung konnte nicht hergestellt werden. Bitte pr\xfcfe die Zugangsdaten","button":"Test Verbindung"},"avalara-address-test-button":{"title":"Adresse Test","success":"Adresse ist g\xfcltig","error":"Adresse ist ung\xfcltig","button":"Test Adresse"}}'),r=JSON.parse('{"avalara-api-test-button":{"title":"API Test","success":"Connection was successfully tested","error":"Connection could not be established. Please check the access data","button":"Test connection"},"avalara-address-test-button":{"title":"Address Test","success":"Address is valid","error":"Address is not valid","button":"Check address"}}');Shopware.Locale.extend("de-DE",n),Shopware.Locale.extend("en-GB",r)}()})();