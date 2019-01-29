import Polyfills from './Polyfills';

//
// Start
//

import Server from './Server';
Server.init();

import Language from './Language';
Language.init();

import HeaderUser from './HeaderUser';
HeaderUser.watch();

import HeaderCategories from './HeaderCategories';
HeaderCategories.watch();

import HeaderSettings from './HeaderSettings';
HeaderSettings.watch();

import Search from './Search';
Search.watch();

import Product from './Product';
Product.watch();

export default {
    HeaderCategories: HeaderCategories,
}
