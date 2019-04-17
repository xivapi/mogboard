import Settings from './Settings';
import HeaderUser from './HeaderUser';
import HeaderCategories from './HeaderCategories';
import Search from './Search';
import Product from './Product';
import ProductAlerts from './ProductAlerts';
import ProductLists from './ProductLists';
import AccountCharacters from './AccountCharacters';
import AccountRetainers from './AccountRetainers';
import AccountPatreon from './AccountPatreon';

/**
 * Basic stuff
 */
Settings.init();
Settings.watch();
HeaderUser.watch();
HeaderCategories.watch();
Search.watch();

/**
 * Item Pages
 */
Product.watch();
ProductAlerts.watch();
ProductLists.watch();

/**
 * Account page
 */
AccountPatreon.watch();

if (typeof appEnabledCharacters !== 'undefined' && appEnabledCharacters === 1) {
    AccountCharacters.watch();
}

if (typeof appEnableRetainers !== 'undefined' && appEnableRetainers === 1) {
    AccountRetainers.watch();
}

/**
 * Export
 */
export default {
    HeaderCategories: HeaderCategories
}
