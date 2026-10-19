import Vue from 'vue';
import axios from 'axios';
import Tinker from './components/Tinker.vue';
import 'codemirror/lib/codemirror.css';
import 'codemirror/theme/idea.css';
import 'codemirror/addon/hint/show-hint.css';
import '../css/app.css';

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

Vue.component('tinker', Tinker);

new Vue({
    el: '#web-tinker',
});
