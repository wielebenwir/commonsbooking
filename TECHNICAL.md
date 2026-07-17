### Formatter

We adhere to [PHPCS](https://github.com/PHPCSStandards/PHP_CodeSniffer) rules defined in the [phpcs.xml](https://github.com/wielebenwir/commonsbooking/blob/master/.phpcs.xml.dist) rules file, as  it is a mature tool and well established in the Wordpress-Plugin development scene.
A program to apply auto-formattable rules of PHPCS is `phpcbf` and we encourage everyone
to configure this tool in their IDE so that contribution commits consist of properly formatted code.
Both are already in the dev dependencies of the repository code.

We have an automatic check [as Github Action](https://github.com/wielebenwir/commonsbooking/tree/master/.github/workflows/phpcbf-check.yml) in our CI/CD-Pipeline, which prevents code contributions that not adhere to the rules.

#### Ignore formatter revisions

We use .git-blame-ignore-revs to track repo-wide cosmetic refactorings by auto format tools like prettier/phpcbf.
See [Github Documentation](https://docs.github.com/de/repositories/working-with-files/using-files/viewing-and-understanding-files#ignore-commits-in-the-blame-view)

You can also configure your local git so it always ignores the revs in that file:

```bash
git config blame.ignoreRevsFile .git-blame-ignore-revs
```
