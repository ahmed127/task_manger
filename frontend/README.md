# tasks

This template should help get you started developing with Vue 3 in Vite.

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd) 
  - [Turn on Custom Object Formatter in Chrome DevTools](http://bit.ly/object-formatters)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)
  - [Turn on Custom Object Formatter in Firefox DevTools](https://fxdx.dev/firefox-devtools-custom-object-formatters/)

## Customize configuration

See [Vite Configuration Reference](https://vite.dev/config/).

## Project Setup

```sh
npm install
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

### Compile and Minify for Production

```sh
# tasks

This project is a small Vue 3 + Vite frontend that includes a Task Manager UI (Kanban-style board) and a small tasks API client.

The Task Manager allows creating tasks (title + body), editing, deleting, and moving tasks between three statuses: Pending (1), In Progress (2), and Done (3).

## Requirements

- Node.js >= 20.19 (or >= 22.12). Use `nvm`/Herd/Volta to install and switch versions.

## Quick start

1. Install dependencies

```bash
npm install
```

2. (Optional) Set API base URL

Create a `.env` file in the project root with:

```env
VITE_API_BASE=https://task.test
```

If not set, the app defaults to `https://task.test`.

3. Run dev server

```bash
npm run dev
```

Open the URL printed by Vite (typically http://localhost:5173 or 5174).

## How the Kanban UI works

- There are 4 tabs at the top: `Board`, `Pending`, `In Progress`, and `Done`.
- `Board` shows three columns (Pending / In Progress / Done). Use the arrow buttons to move tasks between columns.
- Each task card has Edit and Delete actions. Create a new task using the form at the top (title + optional body).
- The Task API endpoints used by the client (see `src/services/tasks.js`) are:
  - GET /tasks
  - POST /tasks
  - PUT /tasks/{id}
  - DELETE /tasks/{id}

## Tailwind CSS

The project uses Tailwind for styling. The Tailwind CSS entry is at `src/assets/tailwind.css`. If you experience errors about `@apply`, make sure the PostCSS pipeline is configured correctly for your installed Tailwind version. I adjusted the project to import the compiled Tailwind CSS, but if you need `@apply` to be processed you may need to adjust `postcss.config.cjs` or install the appropriate PostCSS plugin.

## Notes

- If you need a local mock backend for development, I can add a dev-only mock mode to `src/services/tasks.js`.
- If you want drag-and-drop between columns, I can add `SortableJS` or `Vue Draggable` and wire it up.

If you'd like me to add any of those (mock backend, dnd), tell me which and I'll implement it.
