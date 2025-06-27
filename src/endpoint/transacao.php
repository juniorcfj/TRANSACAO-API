<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/conexao/conexao.php';

return function ($app) {

    // 📌 ROTA 1: POST /transacao
    $app->post('/transacao', function (Request $request, Response $response) {
        $input = $request->getParsedBody();

        // Validação básica
        if (!is_array($input)) {
            $response->getBody()->write(json_encode(["erro" => "JSON inválido"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (!isset($input['id'], $input['valor'], $input['dataHora'])) {
            $response->getBody()->write(json_encode(["erro" => "Campos obrigatórios ausentes"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $id = $input['id'];
        $valor = $input['valor'];
        $dataHora = $input['dataHora'];

        // Validações específicas
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $id)) {
            $response->getBody()->write(json_encode(["erro" => "UUID inválido"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        if (!is_numeric($valor) || $valor < 0) {
            $response->getBody()->write(json_encode(["erro" => "Valor inválido"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        $timestamp = strtotime($dataHora);
        if (!$timestamp || $timestamp > time()) {
            $response->getBody()->write(json_encode(["erro" => "Data inválida ou futura"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        // Inserção
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO transacoes (id, valor, data_hora) VALUES (?, ?, ?)");
            $success = $stmt->execute([$id, $valor, date('Y-m-d H:i:s', $timestamp)]);

            if ($success) {
                // Log para ver se chegou aqui
                file_put_contents(__DIR__ . '/log_exec.txt', date('c') . " | Inserido ID: $id | Valor: $valor\n", FILE_APPEND);

                $response->getBody()->write(json_encode(["mensagem" => "Transação inserida com sucesso"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            } else {
                $response->getBody()->write(json_encode(["erro" => "Falha ao inserir transação"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $response->getBody()->write(json_encode(["erro" => "ID duplicado"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
            }

            $response->getBody()->write(json_encode(["erro" => "Erro no banco", "detalhes" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // 📌 ROTA 2: GET /transacao/{id}
    $app->get('/transacao/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT * FROM transacoes WHERE id = ?");
            $stmt->execute([$id]);
            $transacao = $stmt->fetch();

            if (!$transacao) {
                $response->getBody()->write("Transação não encontrada");
                return $response->withStatus(404);
            }

            $dados = [
                'id' => $transacao['id'],
                'valor' => (float) $transacao['valor'],
                'dataHora' => date('c', strtotime($transacao['data_hora']))
            ];

            $response->getBody()->write(json_encode($dados));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        } catch (PDOException $e) {
            $response->getBody()->write("Erro no banco: " . $e->getMessage());
            return $response->withStatus(500);
        }
    });
};
