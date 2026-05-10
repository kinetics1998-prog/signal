# TOMORROW — що робити коли прокинешся

Дата сесії: 2026-05-10. Я (Claude) працював поки ти спав. Підсумок того що зроблено + чітка послідовність кроків що залишилось.

---

## TL;DR — 3 кроки на 10 хвилин і все запрацює

```
1. Перезалити save_profile.php (зараз там SQL замість PHP — CORS блокує)
2. Відкликати старий bot token + вписати новий у signal_config.php
3. Smoke-test (заповнити onboarding форму, перевірити що рядок з'явився у БД)
```

---

## Крок 1 — Виправити save_profile.php (3 хв)

**Проблема:** при попередньому редагуванні через Hostinger File Manager
у `save_profile.php` випадково вставився SQL з моєї відповіді замість PHP-коду.
curl підтверджує: сервер віддає `CREATE TABLE...` як plain text. PHP не виконується.

**Як виправити:**

1. У новій вкладці браузера відкрий:
   **https://raw.githubusercontent.com/kinetics1998-prog/signal/main/orbit/save_profile.php**

   Має побачити PHP-код що починається з `<?php`.

2. Виділи все: **Cmd+A** → **Cmd+C**

3. Hostinger hPanel → **Файли** → File Manager → шлях `public_html/signal/orbit/`

4. Правою кнопкою на `save_profile.php` → **Редагувати** (олівець)

5. У редакторі: **Cmd+A** → **Delete** → **Cmd+V** (вставити PHP-код)

6. **Зберегти** (Save)

**Перевір що спрацювало:**

Відкрий у браузері: **https://chysto.media/signal/orbit/save_profile.php**

Має побачити: `{"error":"method_not_allowed"}`

Якщо бачиш SQL чи HTML → крок 5 не спрацював, повтори.

---

## Крок 2 — Bot token (5 хв)

**Чому зараз:** ліди зберігаються у БД нормально, але без bot token ти не отримуєш
Telegram-сповіщення про нових учасників. Без сповіщень доведеться кожен раз
самому лазити у phpMyAdmin перевіряти.

**Дії:**

1. Telegram → @BotFather → `/revoke` → обери "Biz Digest Bot" → підтверди
   (старий токен `8744899644:AAF...` припинить працювати — це безпечно)

2. BotFather видасть новий токен — **скопіюй** одразу у password manager
   (Apple Keychain, 1Password, Bitwarden — будь-куди надійне)

3. Дізнайся свій chat_id:
   - У Telegram знайди `@userinfobot` → напиши йому будь-що → отримаєш `Id: 123456789`
   - Це твоє чат-ID (потрібно щоб бот знав куди писати)

4. Дай боту дозвіл писати тобі: відкрий бота у Telegram → натисни `/start`

5. Hostinger File Manager → home `/home/u227106289/` (де лежить `.chysto_env`)
   → правою на `signal_config.php` → Редагувати

6. Знайди блок `'telegram'` і заміни 2 рядки:
   ```php
   'bot_token' => 'СЮДИ_НОВИЙ_BOT_TOKEN',  // ← встав новий токен
   'chat_id'   => 'СЮДИ_ТВІЙ_CHAT_ID',     // ← встав свій chat_id (число)
   ```

7. **Зберегти**.

---

## Крок 3 — Smoke-test (2 хв)

Перевірити що все ланцюгом працює: форма → backend → БД → Telegram-сповіщення.

1. Відкрий **https://kinetics1998-prog.github.io/signal/orbit/onboarding.html**
   - **Cmd+Shift+R** щоб скинути кеш
2. Пройди 5 кроків з тестовими даними:
   - Імʼя: `Тест`
   - Email: `test-onboarding@example.com`
   - Компанія: `Тест ТОВ`
   - Решту полів — будь що
3. На останньому кроці постав **галочку згоди**
4. **Підтвердити та надіслати ✓**
5. Має побачити "Профіль створено"
6. Через 1-2 секунди у Telegram має прийти повідомлення з `@BizDigestBot`
   (або як ти бота назвеш): "**Новий учасник SIGNAL #N** ..."

**Перевірка у БД:**
phpMyAdmin → `u227106289_signal` → таблиця `signal_profiles` → Обзор →
має бути рядок з email `test-onboarding@example.com`.

**Видали тестовий рядок** після перевірки:
```sql
DELETE FROM signal_profiles WHERE email = 'test-onboarding@example.com';
```

---

## Що Я зробив поки ти спав

### Виправлено в коді (запушено в main)
- **partner.html** — fetch URL був відносний `'orbit/save_lead.php'` що ламало
  квіз з github.io (404). Замінив на абсолютний `'https://chysto.media/signal/orbit/save_lead.php'`
- **catalog/lime.html** — приховав PDF-кнопку "Скачати PDF (4 MB)" з `display:none`
  бо файл `assets/lime-katalog-2026.pdf` не залитий (404 у Network).
  Re-enable: залий PDF + прибери `display:none` з тегу `<section class="download">`

### Нові файли в репо
- **TOMORROW.md** — цей файл
- **ROADMAP.md** — стратегія Q2/Q3/Q4 на основі McKinsey-розбору + наші
  локнуті рішення з CEO-review
- **robots.txt** — для пошукових систем (allow all + disallow /orbit/)
- **sitemap.xml** — карта сайту з 6 сторінками для SEO
- **DEPLOY.md** — оновлений з warning про save_profile.php gotcha

### QA-аудит live сайту
| Перевірка | Статус |
|---|---|
| 7 сторінок повертають 200 | ✓ всі ОК |
| 7 sample LIME фото завантажуються | ✓ всі ОК |
| `/assets/lime-katalog-2026.pdf` | ❌ 404 (приховав button) |
| `save_lead.php` backend | ✓ працює (405 на GET) |
| `save_profile.php` backend | ❌ зламаний (SQL замість PHP) |
| robots.txt + sitemap.xml | ❌ були відсутні (створив) |

---

## Якщо щось пішло не так

**save_profile.php після Кроку 1 все ще повертає SQL замість JSON:**
- Імовірно крок 4-5 не спрацював. Перевір що ти РЕДАГУЄШ файл, не створюєш новий.
- Альтернатива: видали `save_profile.php` повністю → завантаж новий через "Upload" кнопку.

**Telegram бот не пише тобі при smoke-test:**
- Перевір що в `signal_config.php` поля `bot_token` і `chat_id` ЗАПОВНЕНІ (без `СЮДИ_...`)
- Перевір що ти `/start`-нув бота у Telegram (інакше він не може тобі писати)
- Подивись Hostinger error log: hPanel → Аналітика → Логи → знайди рядок з `SIGNAL: telegram notify failed`

**Onboarding форма дає "Помилка збереження":**
- DevTools (Cmd+Option+I у Safari) → Network → submit ще раз → клікни на `save_profile.php` → таб **Response** → пришли скрін у чат

---

## Що далі (після того як 3 кроки виконані)

Дивись **ROADMAP.md** — там розписано Q2/Q3/Q4 з реальними метриками.

Коротко найближча задача (Q2 травень-червень):
- Запросити 30 з твоїх 20-50 партнерів заповнити `orbit/onboarding.html`
- Без 30+ заповнених профілів AI-matching у наступному кварталі = театр

---

Питання — пиши у чат коли прокинешся. Гарного сну.
