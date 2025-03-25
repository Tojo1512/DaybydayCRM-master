<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'external_id',
        'name',
        'email',
        'primary_number',
        'secondary_number',
        'client_id',
        'is_primary',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
    /**
     * Renvoie la relation associée à ce contact.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function getRelated()
    {
        // Renvoyer la relation client au lieu du modèle
        return $this->client();
    }
    
    /**
     * Crée une requête pour vérifier l'existence d'une relation.
     * Cette méthode est requise pour les requêtes "whereHas" d'Eloquent.
     *
     * @param Builder $query
     * @param Builder $parentQuery
     * @param string $type
     * @param string $alias
     * @return Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        // Cette implémentation de base délègue à la relation client
        $relation = $this->client();
        
        if (method_exists($relation, 'getRelationExistenceQuery')) {
            return $relation->getRelationExistenceQuery($query, $parentQuery, $columns);
        }
        
        // Fallback si la relation ne possède pas cette méthode
        return $query->select($columns)->whereColumn(
            $this->getQualifiedKeyName(), '=', $this->client()->getQualifiedForeignKeyName()
        );
    }

    /**
     * Crée une requête pour compter l'existence d'une relation.
     * Cette méthode est requise pour les requêtes "has" d'Eloquent avec un opérateur de comparaison.
     *
     * @param Builder $query
     * @param Builder $parentQuery
     * @return Builder
     */
    public function getRelationExistenceCountQuery(Builder $query, Builder $parentQuery)
    {
        return $this->getRelationExistenceQuery(
            $query, $parentQuery, new \Illuminate\Database\Query\Expression('count(*)')
        );
    }

    /**
     * Crée une nouvelle instance de requête sans charger les relations.
     *
     * @return Builder
     */
    public function newQueryWithoutRelationships()
    {
        return $this->newQuery();
    }
}
