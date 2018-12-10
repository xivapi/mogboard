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

import HeaderCategoriesSelection from './HeaderCategoriesSelection';
HeaderCategoriesSelection.watch();

import HeaderSettings from './HeaderSettings';
HeaderSettings.watch();


import Search from './Search';
Search.watch();

import ProductPricing from './ProductPricing';
import ProductHistory from './ProductHistory';
import ProductCrossWorld from './ProductCrossWorld';

export default {
    ProductPricing: ProductPricing,
    ProductHistory: ProductHistory,
    ProductCrossWorld: ProductCrossWorld,
}
