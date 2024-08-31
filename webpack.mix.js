const mix = require('laravel-mix');
require('laravel-mix-vue3');

mix.js('resources/js/app.js', 'public/js')
   .vue()
   .postCss('resources/css/app.css', 'public/css', [
       //
   ]);
