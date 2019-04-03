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

import SettingsCharacters from './SettingsCharacters';
SettingsCharacters.watch();

export default {
    HeaderCategories: HeaderCategories
}
