import Polyfills from './Polyfills';

import Server from './Server';
Server.init();

import HeaderCategories from './HeaderCategories';
HeaderCategories.watch();

import HeaderCategoriesSelection from './HeaderCategoriesSelection';
HeaderCategoriesSelection.watch();

import Search from './Search';
Search.watch();

import ProductPricing from './ProductPricing';
import ProductHistory from './ProductHistory';

export default {
    ProductPricing: ProductPricing,
    ProductHistory: ProductHistory,
}
