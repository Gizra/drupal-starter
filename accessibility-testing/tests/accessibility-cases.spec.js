import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import path from 'path';
import { createHtmlReport } from 'axe-html-reporter';

// WCAG 2.2 AA tags are not cumulative with 2.1/2.0 in axe-core's tag
// scheme, so all three sets must be requested explicitly.
const WCAG_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

// Key page templates seeded by server_default_content - the set a fresh
// client build inherits without needing any project-specific content.
const PAGES = [
  { name: 'front-page', path: '/' },
  { name: 'landing-page', path: '/contact' },
  { name: 'news-article', path: '/news/blind-texts' },
  { name: 'login-page', path: '/user/login' },
  { name: '404-page', path: '/this-page-does-not-exist' },
];

test.describe('WCAG 2.2 AA Accessibility', () => {
  for (const { name, path: pagePath } of PAGES) {
    test(`Accessibility - ${name}`, async ({ page }, testInfo) => {
      await page.goto(pagePath);
      await page.waitForLoadState('networkidle');

      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(WCAG_TAGS)
        .analyze();

      const reportDir = path.join('test-results', 'accessibility-reports');
      const reportFileName = `${name}.html`;
      createHtmlReport({
        results: accessibilityScanResults,
        options: {
          outputDir: reportDir,
          reportFileName,
        },
      });

      // Attach the WCAG report so it's viewable from within the Playwright
      // HTML report, instead of a separate directory.
      await testInfo.attach(`WCAG report - ${name}`, {
        path: path.join(reportDir, reportFileName),
        contentType: 'text/html',
      });

      expect(accessibilityScanResults.violations).toEqual([]);
    });
  }
});
