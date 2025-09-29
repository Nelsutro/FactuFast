# Site

This project was generated using [Angular CLI](https://github.com/angular/angular-cli) version 19.2.15.

## Development server

To start a local development server, run:

```bash
ng serve
```

Once the server is running, open your browser and navigate to `http://localhost:4200/`. The application will automatically reload whenever you modify any of the source files.

## Code scaffolding

Angular CLI includes powerful code scaffolding tools. To generate a new component, run:

```bash
ng generate component component-name
```

For a complete list of available schematics (such as `components`, `directives`, or `pipes`), run:

```bash
ng generate --help
```

## Building

To build the project run:

```bash
ng build
```

This will compile your project and store the build artifacts in the `dist/` directory. By default, the production build optimizes your application for performance and speed.

## OAuth configuration

The login screen now supports authentication via Google, Microsoft and Apple. Make sure the Angular environment exposes the SPA origin so the backend can generate the proper callback URLs:

```ts
// src/environments/environment.ts
export const environment = {
	production: false,
	apiUrl: 'http://api.factufast.local/api',
	appUrl: 'http://localhost:4200',
	appName: 'FactuFast',
	version: '1.0.0'
};
```

Use the same `appUrl` value when registering redirect URIs in each OAuth provider. The SPA listens on `/oauth/callback` and will close the popup automatically after a successful login.

## Running unit tests

To execute unit tests with the [Karma](https://karma-runner.github.io) test runner, use the following command:

```bash
ng test
```

## Running end-to-end tests

For end-to-end (e2e) testing, run:

```bash
ng e2e
```

Angular CLI does not come with an end-to-end testing framework by default. You can choose one that suits your needs.

## Additional Resources

For more information on using the Angular CLI, including detailed command references, visit the [Angular CLI Overview and Command Reference](https://angular.dev/tools/cli) page.
