# Medbook Queue Frontend

Angular 19 single-page client for the Medbook queue API.
I selected Angular not only because  I have prior experience with Angular but also because the brief prefers it. I used a standalone component and no router because there is only one page.

## Run locally

Install dependencies and start the development server:

```bash
npm install
ng serve
```

Open `http://localhost:4200/`. The development proxy sends `/api` requests to `http://127.0.0.1:8001`, so start the Laravel backend first.

## Structure

- `src/app/app.component.*` composes the page and owns API state.
- `src/app/components/sidebar/` contains navigation and section scrolling.
- `src/app/components/queue-board/` displays the calculated queue and active session.
- `src/app/components/customer-form/` handles customer registration input.
- `src/app/services/queue.service.ts` contains the HTTP calls.

The frontend does not calculate queue order. It displays the backend’s effective priority, waiting time, position, and next customer, and sends status actions back to the API. API validation messages are shown to the user. A refresh control reloads the current queue, and the theme toggle is stored locally.

## Commands

```bash
npm run build
npm test
```

The business-rule tests live in the Laravel backend. Angular tests should cover component rendering and service interaction as the frontend grows.
