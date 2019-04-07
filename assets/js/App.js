import Settings from './Settings';
Settings.init();
Settings.watch();

import HeaderUser from './HeaderUser';
HeaderUser.watch();

import HeaderCategories from './HeaderCategories';
HeaderCategories.watch();

import Search from './Search';
Search.watch();

import Product from './Product';
Product.watch();

import ProductAlerts from './ProductAlerts';
ProductAlerts.watch();

import ProductLists from './ProductLists';
ProductLists.watch();

import AccountCharacters from './AccountCharacters';
AccountCharacters.watch();

import AccountPatreon from './AccountPatreon';
AccountPatreon.watch();

export default {
    HeaderCategories: HeaderCategories
}
