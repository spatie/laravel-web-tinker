import Vue from 'vue';
import axios from 'axios';

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

Vue.component('tinker', require('./components/Tinker.vue'));

new Vue({
    el: '#web-tinker',
});
