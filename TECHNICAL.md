### Formatter

We use .git-blame-ignore-revs to track repo-wide cosmetic refactorings by auto format tools like prettier/phpcbf.
See [Github Documentation](https://docs.github.com/de/repositories/working-with-files/using-files/viewing-and-understanding-files#ignore-commits-in-the-blame-view)

You can also configure your local git so it always ignores the revs in that file:

```bash
git config blame.ignoreRevsFile .git-blame-ignore-revs
```
