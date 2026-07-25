-- Opcional: apaga TODOS os cursos EAD e matrículas EAD de UMA escola (substitua 1 pelo id_admin).
-- Use só se o conteúdo for de exemplo. Não mexe em trilhas/matriculas comerciais.
-- Data: 2026-07-24

SET @id_admin := 1; -- <<< troque

DELETE m FROM lms_matricula_ead m
INNER JOIN lms_cursos c ON c.id = m.id_curso
WHERE c.id_admin = @id_admin OR m.id_admin = @id_admin;

DELETE a FROM lms_vitrine_assinaturas a
INNER JOIN lms_cursos c ON c.id = a.id_curso
WHERE c.id_admin = @id_admin OR a.id_escola_assinante = @id_admin OR a.id_escola_criadora = @id_admin;

-- Conteúdo (ordem por FK lógica)
DELETE q FROM lms_questoes q
INNER JOIN lms_atividades at ON at.id = q.id_atividade
INNER JOIN lms_aulas au ON au.id = at.id_aula
INNER JOIN lms_modulos mo ON mo.id = au.id_modulo
INNER JOIN lms_cursos c ON c.id = mo.id_curso
WHERE c.id_admin = @id_admin;

DELETE at FROM lms_atividades at
INNER JOIN lms_aulas au ON au.id = at.id_aula
INNER JOIN lms_modulos mo ON mo.id = au.id_modulo
INNER JOIN lms_cursos c ON c.id = mo.id_curso
WHERE c.id_admin = @id_admin;

DELETE rp FROM lms_roleplay_cenarios rp
INNER JOIN lms_aulas au ON au.id = rp.id_aula
INNER JOIN lms_modulos mo ON mo.id = au.id_modulo
INNER JOIN lms_cursos c ON c.id = mo.id_curso
WHERE c.id_admin = @id_admin;

DELETE v FROM lms_videos v
INNER JOIN lms_aulas au ON au.id = v.id_aula
INNER JOIN lms_modulos mo ON mo.id = au.id_modulo
INNER JOIN lms_cursos c ON c.id = mo.id_curso
WHERE c.id_admin = @id_admin;

DELETE mt FROM lms_materiais mt
INNER JOIN lms_aulas au ON au.id = mt.id_aula
INNER JOIN lms_modulos mo ON mo.id = au.id_modulo
INNER JOIN lms_cursos c ON c.id = mo.id_curso
WHERE c.id_admin = @id_admin;

DELETE au FROM lms_aulas au
INNER JOIN lms_modulos mo ON mo.id = au.id_modulo
INNER JOIN lms_cursos c ON c.id = mo.id_curso
WHERE c.id_admin = @id_admin;

DELETE mo FROM lms_modulos mo
INNER JOIN lms_cursos c ON c.id = mo.id_curso
WHERE c.id_admin = @id_admin;

DELETE FROM lms_cursos WHERE id_admin = @id_admin;
