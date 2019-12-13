import { element, by } from 'protractor';
import { Utils } from '../utils';
import {} from 'jasmine';

describe('App', () => {
    let utils: Utils;

    beforeEach(() => {
        utils = new Utils;
    });

    afterEach(() => {
        utils.cleanBrowser();
    });

    it('maintenance mode page should be activated', async() => {
        await utils.reloadApp([
            'configs_maintenance'
        ]);

        const pageDesc = await element(by.css('h1')).getText();

        expect(pageDesc).toEqual('Under maintenance.');
    });

    it('logout', async() => {
        await utils.reloadApp([
        ], true);

        // click the settings button
        await utils.click('sk-settings-button');

        // click the logout button
        await utils.click('sk-logout-button');

        expect(utils.findElementByText('.sk-fpass', 'Forgot password').count()).toBe(1);
    });

    it('privacy and terms of use', async() => {
        await utils.reloadApp([
        ], true);

        // click the settings button
        await utils.click('sk-settings-button');

        // click the privacy button
        await utils.click('sk-privacy-button');

        // waiting for modal window with privacy
        expect(await utils.waitForElement('show-page')).toBe(true);

        // search for some text in privacy policy
        expect(utils.findElementByText('.modal-wrapper', 'Thank you for visiting our website').count()).toBe(1);

        // close the modal window
        await utils.click('sk-custompage-close');

        // click the terms of use button
        await utils.click('sk-termsofuse-button');

        // waiting for modal window with privacy
        expect(await utils.waitForElement('show-page')).toBe(true);

        // search for some text in terms of use
        expect(utils.findElementByText('.modal-wrapper', 'Welcome to our website').count()).toBe(1);
    });

    it('save email settings', async() => {
        await utils.reloadApp([
        ], true);

        // click the settings button
        await utils.click('sk-settings-button');

        // click the email button
        await utils.click('sk-email-button');

        // search for some text in email questions setings
        expect(utils.findElementByText('.sk-email-notifications-description', 'Control the emails you want to get').count()).toBe(1);

        // click the done button
        await utils.click('sk-email-notification-done-button');

        // should see the success message
        expect(await utils.toaster()).toEqual('Email settings saved');
    });

    it('save push settings', async() => {
        await utils.reloadApp([
        ], true);

        // click the settings button
        await utils.click('sk-settings-button');

        // click the push button
        await utils.click('sk-push-button');

        // search for some text in push questions setings
        expect(utils.findElementByText('.sk-checkbox-question-presentation', 'New Messages').count()).toBe(1);

        // click the done button
        await utils.click('sk-push-notification-done-button');

        // should see the success message
        expect(await utils.toaster()).toEqual('Preferences saved');
    });

    it('gdpr settings yourdata', async() => {
        await utils.reloadApp([
        ], true);

        // click the settings button
        await utils.click('sk-settings-button');

        // click the yourdata button
        await utils.click('sk-yourdata-button');

        // search for some text in gdpr questions setings
        expect(utils.findElementByText('.sk-user-data-note', 'You have entrusted us with the following personal data').count()).toBe(1);

       // click the data profile edit button
        await utils.click('sk-gdpr-profile-edit-btn');

        // profile edit should be opened
        expect(await utils.waitForElement('sk-avatar-mask')).toBe(true);

        await utils.waitForElement('back-button');

        // click the back button
        await utils.click('back-button',2);

       // click the data download button
        await utils.click('sk-user-data-download-btn');

        // should see the success message
        expect(await utils.toaster()).toEqual('You have successfully sent a request');

        // click the data deletion button
        await utils.click('sk-user-data-deletion-btn');

        // should see the success message
        expect(await utils.toaster()).toEqual('You have successfully sent a request.');
    });

    it('gdpr settings 3rd party services', async() => {
        await utils.reloadApp([
        ], true);

        // click the settings button
        await utils.click('sk-settings-button');

        // click the 3rd party services button
        await utils.click('sk-3rdpartyservice-button');

        // search for some text in gdpr questions setings
        expect(utils.findElementByText('.sk-user-data-note', 'For business, analytical and administrative purposes').count()).toBe(1);

        // click the request manual deletion button
        await utils.click('sk-third-data-deletion-btn');

        // search for some text in 3rd party services page
        expect(utils.findElementByText('.sk-textarea-question-presentation', 'Send message to admin').count()).toBe(1);

        // click send message button
        await utils.click('sk-gdpr-send-message');

        // should see the message about empty field
        expect(await utils.toaster()).toEqual('Please fill the fields correctly to continue');

        // write message
        await utils.fillInputByCssClass('text-input', 'test message');

        // click send message button
        await utils.click('sk-gdpr-send-message');

        // should see the success message
        expect(await utils.toaster()).toEqual('You have successfully sent a request.');
    });

    it('gdpr settings should be absent if the plugin uninstalled', async() => {
        await utils.reloadApp([
          'configs_gdpr_disable'
        ], true);

        // click the settings button
        await utils.click('sk-settings-button');

       // must not show gdpr text
        expect(utils.findElementByText('.scroll-content', 'General data protection regulation').count()).toBe(0);
    });

    it('delete user profile', async() => {
        await utils.reloadApp([
          'user_not_admin'
        ], true);

        // click the settings button
        await utils.click('sk-settings-button');

        // click delete profile button
        await utils.click('sk-delete-button');

        // should see the message about delete account
        expect(utils.findElementByText('.action-sheet-title', 'If you delete your account').count()).toBe(1);

        // click delete account button
        await utils.click('action-sheet-button',0);

        // sign-in form should be opened
        expect(utils.waitForElement('sk-buttons-inline')).toBe(true);
    });
});
