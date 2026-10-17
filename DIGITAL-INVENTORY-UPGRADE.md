# Digital inventory upgrade

Deploy the changed PHP and Blade files together. Before reopening the store, run:

```sh
php artisan store:secure-digital-files
php artisan optimize:clear
npm run build
```

On hosts without a shell, Admin → Apply updates also runs the storage command.

The storage command copies existing downloads (including versions referenced by older orders) to `storage/app/private`, verifies their contents, then removes the public copies. It is safe to rerun. Resolve any reported missing files from your backups. Product images remain public.

Keep `APP_KEY` unchanged: it signs customer download and payment reminder links. New guest download links last 30 days; signed reminder links last 7 days. Account owners and the browser session that placed an order can also access it. Old unsigned guest links require signing in or a new delivery email from Orders.

The former cleanup migration is deliberately a no-op for new installs. Previously altered keys cannot be reconstructed automatically; restore affected credentials from the original supplier or a verified backup. No current keys are rewritten by this upgrade.

Paste imports default to one exact code or link per line. Select numbered-list or wrapped-URL mode only when appropriate, then review the server preview. Duplicate entries are skipped, including keys already delivered. Importing and removing inventory saves immediately; product settings use their own Save buttons.

Old download versions are retained to honor past purchases. Remove a private file only after confirming no product or order item references it.
