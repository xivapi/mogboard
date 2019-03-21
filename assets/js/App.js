import Server from './Server';
Server.init();

import Language from './Language';
Language.init();

import HeaderUser from './HeaderUser';
HeaderUser.watch();

import HeaderCategories from './HeaderCategories';
HeaderCategories.watch();

import Settings from './Settings';
Settings.watch();

import Search from './Search';
Search.watch();

import Product from './Product';
Product.watch();

import ProductAlerts from './ProductAlerts';
ProductAlerts.watch();

import ProductLists from './ProductLists';
ProductLists.watch();

import SettingsCharacters from './SettingsCharacters';
SettingsCharacters.watch();

export default {
    HeaderCategories: HeaderCategories,
    ProductAlerts: ProductAlerts,
}
